<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class StaffLocalizationTest extends TestCase
{
    use RefreshDatabase;

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
