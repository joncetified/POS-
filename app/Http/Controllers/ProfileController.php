<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CafeCatalog;
use App\Support\FaceRecognition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

    public function faceOptions(Request $request): JsonResponse
    {
        if (! Schema::hasColumn('users', 'face_descriptor')) {
            return response()->json([
                'message' => 'Database hosting belum punya kolom Face Recognition. Import database terbaru atau jalankan migration.',
            ], 422);
        }

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'ready' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
            ],
        ]);
    }

    public function updateFace(Request $request): RedirectResponse
    {
        if (! Schema::hasColumn('users', 'face_descriptor') || ! Schema::hasColumn('users', 'face_registered_at')) {
            return back()->withErrors([
                'face' => 'Database hosting belum punya kolom Face Recognition. Import database terbaru atau jalankan migration.',
            ]);
        }

        $validated = $request->validate([
            'face_descriptor' => ['required', 'string'],
        ]);

        try {
            $descriptor = FaceRecognition::encode(FaceRecognition::descriptorFromJson($validated['face_descriptor']));
        } catch (\InvalidArgumentException $error) {
            return back()->withErrors(['face' => $error->getMessage()]);
        }

        /** @var User $user */
        $user = $request->user();
        $user->forceFill([
            'face_descriptor' => $descriptor,
            'face_registered_at' => now(),
        ])->save();

        return back()->with('status', 'Face Recognition berhasil didaftarkan.');
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
