<?php

namespace Tests\Feature\Core\Auth;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\PasswordResetToken;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Shared\Mail\RecuperarPasswordMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecuperarPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function createOwner(): Usuario
    {
        $plan    = Plan::factory()->pyme()->create();
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->trial()->create(['empresa_id' => $empresa->id, 'plan_id' => $plan->id]);

        return Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'email'      => 'owner@test.com',
            'password'   => Hash::make('password123'),
        ]);
    }

    public function test_queues_email_for_existing_user(): void
    {
        Mail::fake();
        $this->createOwner();

        $this->postJson('/api/auth/recuperar-password', ['email' => 'owner@test.com'])
            ->assertOk();

        Mail::assertQueued(RecuperarPasswordMail::class);
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_returns_200_for_nonexistent_email(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/recuperar-password', ['email' => 'noexiste@test.com'])
            ->assertOk();

        Mail::assertNothingQueued();
    }

    public function test_reset_password_with_valid_token(): void
    {
        $owner = $this->createOwner();
        $token = 'valid-random-token-64-chars-long-padding-chars-here-abc123';

        PasswordResetToken::create([
            'email'      => $owner->email,
            'token'      => hash('sha256', $token),
            'expires_at' => now()->addMinutes(60),
            'created_at' => now(),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'token'                 => $token,
            'email'                 => $owner->email,
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk();

        $this->assertDatabaseMissing('password_reset_tokens', ['used_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['accion' => 'password_changed']);
    }

    public function test_reset_returns_422_for_expired_token(): void
    {
        $owner = $this->createOwner();
        $token = 'expired-token';

        PasswordResetToken::create([
            'email'      => $owner->email,
            'token'      => hash('sha256', $token),
            'expires_at' => now()->subMinute(),
            'created_at' => now()->subHours(2),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'token'                 => $token,
            'email'                 => $owner->email,
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertStatus(422);
    }
}
