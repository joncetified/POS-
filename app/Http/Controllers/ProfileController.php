<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CafeCatalog;
use App\Support\WebAuthn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'store' => CafeCatalog::store(),
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'avatar_crop' => ['nullable', 'string'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        /** @var User $user */
        $user = $request->user();
        $data = ['name' => $validated['name']];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if (! empty($validated['avatar_crop'])) {
            $data['avatar_path'] = $this->storeCroppedAvatar($validated['avatar_crop'], $user);
        }

        $user->update($data);

        return back()->with('status', 'Profil saya berhasil disimpan.');
    }

    public function fingerprintOptions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $challenge = WebAuthn::challenge();
        $request->session()->put('fingerprint_register_challenge', $challenge);

        return response()->json([
            'options' => WebAuthn::registrationOptions($request, $challenge, $user->id, $user->username, $user->name),
        ]);
    }

    public function updateFingerprint(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'credential_id' => ['required', 'string', 'max:512'],
            'client_data_json' => ['required', 'string'],
        ]);

        $challenge = $request->session()->pull('fingerprint_register_challenge');

        if (! $challenge) {
            return back()->withErrors(['fingerprint' => 'Sesi daftar fingerprint sudah habis. Mulai ulang.']);
        }

        try {
            WebAuthn::validateClientData($validated['client_data_json'], 'webauthn.create', $challenge, $request);
            $credentialId = WebAuthn::normalizeCredentialId($validated['credential_id']);
        } catch (InvalidArgumentException $error) {
            return back()->withErrors(['fingerprint' => $error->getMessage()]);
        }

        /** @var User $user */
        $user = $request->user();
        $user->forceFill([
            'biometric_credential_id' => $credentialId,
            'biometric_registered_at' => now(),
        ])->save();

        return back()->with('status', 'Fingerprint login berhasil didaftarkan.');
    }

    private function storeCroppedAvatar(string $dataUrl, User $user): string
    {
        abort_unless(
            preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $dataUrl),
            422,
            'Format crop foto profil tidak valid.'
        );

        $encoded = preg_replace('/^data:image\/(png|jpeg|jpg|webp);base64,/', '', $dataUrl);
        $binary = base64_decode((string) $encoded, true);

        abort_unless($binary !== false && strlen($binary) <= 2 * 1024 * 1024, 422, 'Ukuran foto profil terlalu besar.');

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = 'avatars/user-' . $user->id . '-' . now()->format('YmdHis') . '.jpg';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
