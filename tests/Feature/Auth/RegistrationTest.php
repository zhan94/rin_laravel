<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_staff_registration_is_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'email' => 'uninvited@example.test',
            'password' => 'A synthetic test password',
            'password_confirmation' => 'A synthetic test password',
        ])->assertNotFound();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }
}
