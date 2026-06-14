<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\RolePermission;
use App\Models\User;
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
        $this->authorizeAccessManager($request);

        $actor = $request->user();
        $userCounts = User::query()
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        return view('access-control.index', [
            'store' => CafeCatalog::store(),
            'pages' => PageAccess::pages(),
            'roles' => collect(UserRole::cases())
                ->map(fn (UserRole $role) => [
                    'role' => $role,
                    'permissions' => $this->permissionsForRole($role),
                    'user_count' => (int) ($userCounts[$role->value] ?? 0),
                    'can_manage' => $this->canManageRole($actor->role, $role),
                    'protected_permissions' => $role->requiredPermissions(),
                ]),
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        $this->authorizeAccessManager($request);

        $targetRole = UserRole::tryFrom($role);
        abort_unless($targetRole, 404);
        abort_unless($this->canManageRole($request->user()->role, $targetRole), 403, 'Role akun ini tidak boleh mengubah role tersebut.');

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(PageAccess::permissions())],
        ]);

        DB::transaction(function () use ($targetRole, $validated) {
            $selected = collect($validated['permissions'] ?? []);

            $selected = $selected->merge($targetRole->requiredPermissions());

            foreach (PageAccess::permissions() as $permission) {
                RolePermission::query()->updateOrCreate(
                    [
                        'role' => $targetRole->value,
                        'permission' => $permission,
                    ],
                    [
                        'allowed' => $selected->contains($permission),
                    ],
                );
            }
        });

        return back()->with('status', 'Akses role ' . $targetRole->label() . ' sudah disimpan.');
    }

    private function authorizeAccessManager(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user
                && in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true)
                && $user->hasPermission('page.access_control'),
            403,
            'Role akun ini tidak punya akses mengatur role.',
        );
    }

    private function canManageRole(UserRole $actorRole, UserRole $targetRole): bool
    {
        if ($actorRole === UserRole::SuperAdmin) {
            return true;
        }

        if ($actorRole === UserRole::Admin) {
            return in_array($targetRole, [
                UserRole::Cashier,
                UserRole::Warehouse,
                UserRole::Manager,
                UserRole::Owner,
                UserRole::Customer,
            ], true);
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function permissionsForRole(UserRole $role): array
    {
        $permissions = collect($role->permissions());

        RolePermission::query()
            ->where('role', $role->value)
            ->get(['permission', 'allowed'])
            ->each(function (RolePermission $override) use (&$permissions): void {
                $permissions = $override->allowed
                    ? $permissions->push($override->permission)
                    : $permissions->reject(fn (string $value) => $value === $override->permission);
            });

        $permissions = $permissions->merge($role->requiredPermissions());

        return $permissions->unique()->values()->all();
    }
}
