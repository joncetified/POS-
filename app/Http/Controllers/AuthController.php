<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Mail\RegistrationVerificationCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak sesuai.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if (! $request->user()->email_verified_at) {
            $user = $request->user();
            Auth::logout();
            $request->session()->put('pending_verification_user_id', $user->id);

            return redirect()
                ->route('verification.notice')
                ->with('status', 'Verifikasi email dulu sebelum masuk.');
        }

        return redirect()->intended(route('pos.index'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => UserRole::Cashier,
        ]);

        $this->sendVerificationCode($user);

        $request->session()->put('pending_verification_user_id', $user->id);

        return redirect()
            ->route('verification.notice')
            ->with('status', 'Kode verifikasi sudah dikirim ke email.');
    }

    public function showVerification(Request $request): View
    {
        return view('auth.verify-email', [
            'email' => $this->pendingUser($request)?->email,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! $this->verificationCodeIsValid($user, $validated['code'])) {
            return back()
                ->withErrors(['code' => 'Kode verifikasi tidak valid atau sudah kedaluwarsa.'])
                ->withInput(['email' => $validated['email']]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
        ])->save();

        Auth::login($user);
        $request->session()->forget('pending_verification_user_id');
        $request->session()->regenerate();

        return redirect()->route('pos.index');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->whereNull('email_verified_at')
            ->first();

        if ($user) {
            $this->sendVerificationCode($user);
            $request->session()->put('pending_verification_user_id', $user->id);
        }

        return back()
            ->withInput(['email' => $validated['email']])
            ->with('status', 'Kalau email belum terverifikasi, kode baru sudah dikirim.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function sendVerificationCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_code' => $code,
            'email_verification_expires_at' => now()->addMinutes(10),
        ])->save();

        Mail::to($user->email)->send(new RegistrationVerificationCode($user, $code));
    }

    private function verificationCodeIsValid(User $user, string $code): bool
    {
        return $user->email_verification_code === $code
            && $user->email_verification_expires_at
            && $user->email_verification_expires_at->isFuture();
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('pending_verification_user_id');

        return $userId ? User::query()->find($userId) : null;
    }
}
