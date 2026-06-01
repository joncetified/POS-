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
use Illuminate\Validation\Rule;
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

        if ($user->role === UserRole::SuperAdmin) {
            return back()->with('status', 'Akses Super Admin dikunci supaya sistem tidak kehilangan akun pengelola utama.');
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(PageAccess::permissions())],
        ]);

        $selected = collect($validated['permissions'] ?? []);

        DB::transaction(function () use ($user, $selected) {
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

        return back()->with('status', 'Akses halaman untuk ' . $user->name . ' sudah disimpan.');
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === UserRole::SuperAdmin, 403, 'Hanya Super Admin yang boleh mengatur akses user.');
    }
}
