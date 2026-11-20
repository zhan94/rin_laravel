<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class StaffLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_visits_use_the_configured_default_and_saved_choices_take_precedence(): void
    {
        config(['localization.default_locale' => 'de']);
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9');

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('locale.current', 'de'));

        $this->get('/login?lang=es')
            ->assertOk()
            ->assertCookie('app_locale', 'es')
            ->assertInertia(fn (Assert $page) => $page->where('locale.current', 'es'));

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('locale.current', 'de'));

        $this->withCookie('app_locale', 'es')->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('locale.current', 'es'));
    }

    /** @return array<string, array{string, string, string, string}> */
    public static function localizedContent(): array
    {
        return [
            'German' => ['de', 'RIN-Mitarbeiterhandbuch', 'Füllen Sie das Feld Vorname aus.', 'Kunde nicht gefunden.'],
            'Spanish' => ['es', 'Manual del personal de RIN', 'Complete el campo nombre.', 'No se encontró al cliente.'],
        ];
    }

    #[DataProvider('localizedContent')]
    public function test_staff_manual_and_api_errors_use_the_selected_locale(string $locale, string $title, string $validation, string $notFound): void
    {
        $this->actingAs(User::factory()->create());

        $manual = $this->get('/manual?lang='.$locale)->assertOk()->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $manual);
        $this->assertStringContainsString('<html lang="'.$locale.'">', $manual->getFile()->getContent());
        $this->assertStringContainsString('<h1>'.$title.'</h1>', $manual->getFile()->getContent());

        $this->withHeader('X-Locale', $locale)
            ->postJson(route('api.v1.customers.store'), [])
            ->assertUnprocessable()
            ->assertJsonFragment(['code' => 'validation_error', 'message' => $validation]);

        $this->getJson('/api/v1/customers/999999')
            ->assertNotFound()
            ->assertJsonPath('errors.0.message', $notFound);
    }
}
