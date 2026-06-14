<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\RegistrationVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_register_creates_customer_and_sends_verification_code(): void
    {
        Mail::fake();

        $response = $this->post(route('register.store'), [
            'username' => 'kasirbaru',
            'email' => 'kasir@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'kasir@example.com')->firstOrFail();

        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame('kasirbaru', $user->name);
        $this->assertSame('kasirbaru', $user->username);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_verification_code);
        $this->assertNotNull($user->email_verification_expires_at);

        Mail::assertSent(RegistrationVerificationCode::class, function (RegistrationVerificationCode $mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->code === $user->email_verification_code;
        });
    }

    public function test_user_can_verify_registration_code_and_login(): void
    {
        $user = User::factory()->create([
            'email' => 'kasir@example.com',
            'email_verified_at' => null,
            'email_verification_code' => '123456',
            'email_verification_expires_at' => now()->addMinutes(10),
        ]);

        $this->post(route('verification.verify'), [
            'email' => $user->email,
            'code' => '123456',
        ])->assertRedirect(route('pos.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->email_verification_code);
    }

    public function test_unverified_user_cannot_login_before_verification(): void
    {
        $user = User::factory()->create([
            'username' => 'kasirlogin',
            'name' => 'kasirlogin',
            'password' => 'password123',
            'email_verified_at' => null,
        ]);

        $this->post(route('login.store'), [
            'username' => 'kasirlogin',
            'password' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $this->assertGuest();
    }

    public function test_verified_user_can_login_with_registered_face_credential(): void
    {
        $descriptor = $this->faceDescriptor();
        $user = User::factory()->create([
            'username' => 'kasirface',
            'face_descriptor' => $descriptor,
            'face_registered_at' => now(),
        ]);

        $this->postJson(route('login.face.options'), [
            'username' => 'kasirface',
        ])->assertOk()
            ->assertJsonPath('ready', true);

        $this->postJson(route('login.face'), [
            'username' => 'kasirface',
            'face_descriptor' => $descriptor,
        ])
            ->assertOk()
            ->assertJson(['redirect' => route('pos.index')]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_face_login_rejects_unregistered_or_mismatched_credential(): void
    {
        User::factory()->create([
            'username' => 'tanpaface',
            'face_descriptor' => null,
        ]);

        $this->postJson(route('login.face.options'), [
            'username' => 'tanpaface',
        ])->assertUnprocessable();

        User::factory()->create([
            'username' => 'facebeda',
            'face_descriptor' => $this->faceDescriptor(0.2),
            'face_registered_at' => now(),
        ]);

        $this->postJson(route('login.face.options'), [
            'username' => 'facebeda',
        ])->assertOk();

        $this->postJson(route('login.face'), [
            'username' => 'facebeda',
            'face_descriptor' => $this->faceDescriptor(2.2),
        ])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_unverified_user_cannot_login_with_face_before_verification(): void
    {
        $descriptor = $this->faceDescriptor();
        $user = User::factory()->unverified()->create([
            'username' => 'facepending',
            'face_descriptor' => $descriptor,
            'face_registered_at' => now(),
        ]);

        $this->postJson(route('login.face.options'), [
            'username' => 'facepending',
        ])->assertOk();

        $this->postJson(route('login.face'), [
            'username' => 'facepending',
            'face_descriptor' => $descriptor,
        ])->assertForbidden()
            ->assertJson(['redirect' => route('verification.notice')]);

        $this->assertGuest();
        $this->assertSame($user->id, session('pending_verification_user_id'));
    }

    private function faceDescriptor(float $seed = 0.1): string
    {
        return json_encode(array_fill(0, 128, $seed), JSON_THROW_ON_ERROR);
    }
}
