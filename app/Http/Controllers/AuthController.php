<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Mail\RegistrationVerificationCode;
use App\Models\User;
use App\Support\CafeCatalog;
use App\Support\WebAuthn;
use InvalidArgumentException;
use Illuminate\Http\JsonResponse;
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
        return view('auth.login', [
            'store' => CafeCatalog::store(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'Username atau password tidak sesuai.'])
                ->onlyInput('username');
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

        return redirect()->intended(route($this->homeRouteFor($request->user())));
    }

    public function fingerprintOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('username', $validated['username'])
            ->first();

        if (! $user || ! $user->biometric_credential_id) {
            return response()->json([
                'message' => 'Fingerprint belum terdaftar untuk username ini.',
                'errors' => ['username' => ['Fingerprint belum terdaftar untuk username ini.']],
            ], 422);
        }

        $challenge = WebAuthn::challenge();
        $request->session()->put('fingerprint_login_challenge', $challenge);
        $request->session()->put('fingerprint_login_username', $user->username);

        return response()->json([
            'options' => WebAuthn::authenticationOptions($request, $challenge, $user->biometric_credential_id),
        ]);
    }

    public function fingerprintLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'credential_id' => ['required', 'string', 'max:512'],
            'client_data_json' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $challenge = $request->session()->pull('fingerprint_login_challenge');
        $challengeUsername = $request->session()->pull('fingerprint_login_username');

        $user = User::query()
            ->where('username', $validated['username'])
            ->first();

        if (! $challenge || ! hash_equals((string) $challengeUsername, $validated['username']) || ! $user || ! $user->biometric_credential_id) {
            return response()->json([
                'message' => 'Sesi fingerprint tidak valid. Mulai ulang scan fingerprint.',
                'errors' => ['username' => ['Sesi fingerprint tidak valid.']],
            ], 422);
        }

        try {
            WebAuthn::validateClientData($validated['client_data_json'], 'webauthn.get', $challenge, $request);
            $credentialId = WebAuthn::normalizeCredentialId($validated['credential_id']);
        } catch (InvalidArgumentException $error) {
            return response()->json([
                'message' => $error->getMessage(),
                'errors' => ['credential_id' => [$error->getMessage()]],
            ], 422);
        }

        if (! hash_equals($user->biometric_credential_id, $credentialId)) {
            return response()->json([
                'message' => 'Fingerprint tidak cocok dengan akun ini.',
                'errors' => ['credential_id' => ['Fingerprint tidak cocok dengan akun ini.']],
            ], 422);
        }

        if (! $user->email_verified_at) {
            $request->session()->put('pending_verification_user_id', $user->id);

            return response()->json([
                'message' => 'Verifikasi email dulu sebelum masuk.',
                'redirect' => route('verification.notice'),
            ], 403);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json([
            'redirect' => route($this->homeRouteFor($user)),
        ]);
    }

    public function showRegister(): View
    {
        return view('auth.register', [
            'store' => CafeCatalog::store(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:80', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([
            'name' => $validated['username'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => UserRole::Customer,
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
            'store' => CafeCatalog::store(),
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

        return redirect()->route($this->homeRouteFor($user));
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

    private function homeRouteFor(User $user): string
    {
        foreach ([
            'page.pos' => 'pos.index',
            'page.dashboard' => 'dashboard.index',
            'page.products' => 'products.index',
            'page.customer_menu' => 'customer.menu',
            'page.reports' => 'reports.index',
            'page.sales' => 'sales.index',
            'page.settings' => 'settings.index',
            'page.qr_tables' => 'customer.qr.index',
        ] as $permission => $routeName) {
            if ($user->hasPermission($permission)) {
                return $routeName;
            }
        }

        return 'dashboard.index';
    }
}
