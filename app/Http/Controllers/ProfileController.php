<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CafeCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
