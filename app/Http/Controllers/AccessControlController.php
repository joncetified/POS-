<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserPermission;
use App\Support\CafeCatalog;
use App\Support\PageAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccessControlController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        return view('access-control.index', [
            'store' => CafeCatalog::store(),
            'pages' => PageAccess::pages(),
            'users' => User::query()
                ->with('permissionOverrides')
                ->orderBy('role')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'alpha_dash', 'min:3', 'max:80', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'avatar_crop' => ['nullable', 'string'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(PageAccess::permissions())],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $accountData = [
                'name' => $validated['name'] ?? $user->name,
                'username' => $validated['username'] ?? $user->username,
                'email' => $validated['email'] ?? $user->email,
            ];

            if ($user->role !== UserRole::SuperAdmin && isset($validated['role'])) {
                $accountData['role'] = $validated['role'];
            }

            if (! empty($validated['password'])) {
                $accountData['password'] = Hash::make($validated['password']);
            }

            if (! empty($validated['avatar_crop'])) {
                $accountData['avatar_path'] = $this->storeCroppedAvatar($validated['avatar_crop'], $user);
            }

            $user->update($accountData);

            if ($user->role === UserRole::SuperAdmin) {
                return;
            }

            $selected = collect($validated['permissions'] ?? []);

            foreach (PageAccess::permissions() as $permission) {
                UserPermission::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'permission' => $permission,
                    ],
                    [
                        'allowed' => $selected->contains($permission),
                    ],
                );
            }
        });

        return back()->with('status', 'Data user ' . $user->name . ' sudah disimpan.');
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === UserRole::SuperAdmin, 403, 'Hanya Super Admin yang boleh mengatur akses user.');
    }

    private function storeCroppedAvatar(string $dataUrl, User $user): string
    {
        abort_unless(
            preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $dataUrl),
            422,
            'Format crop foto user tidak valid.'
        );

        $encoded = preg_replace('/^data:image\/(png|jpeg|jpg|webp);base64,/', '', $dataUrl);
        $binary = base64_decode((string) $encoded, true);

        abort_unless($binary !== false && strlen($binary) <= 2 * 1024 * 1024, 422, 'Ukuran foto user terlalu besar.');

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = 'avatars/user-' . $user->id . '-' . now()->format('YmdHis') . '.jpg';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
