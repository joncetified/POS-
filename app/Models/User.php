<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\CafeCatalog;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'avatar_path',
        'face_descriptor',
        'face_registered_at',
        'password',
        'email_verification_code',
        'email_verification_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
        'face_descriptor',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'face_registered_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Accept older hosted database values without throwing enum cast errors.
     *
     * @return Attribute<UserRole, UserRole|string|null>
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): UserRole => $this->resolveRole($value),
            set: fn (mixed $value): string => $value instanceof UserRole
                ? $value->value
                : $this->resolveRole($value)->value,
        );
    }

    public function roleLabel(): string
    {
        return $this->role->label();
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return CafeCatalog::publicStorageUrl($this->avatar_path);
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        $permissions = collect($this->role->permissions());

        $this->rolePermissionMap()->each(function (bool $allowed, string $permission) use (&$permissions) {
            $permissions = $allowed
                ? $permissions->push($permission)
                : $permissions->reject(fn (string $value) => $value === $permission);
        });

        return $permissions
            ->merge($this->role->requiredPermissions())
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(string $permission): bool
    {
        if (in_array($permission, $this->role->requiredPermissions(), true)) {
            return true;
        }

        $override = $this->rolePermissionMap()->get($permission);

        if ($override !== null) {
            return $override;
        }

        return in_array($permission, $this->role->permissions(), true);
    }

    /**
     * @return Collection<string, bool>
     */
    private function rolePermissionMap(): Collection
    {
        if (! Schema::hasTable('role_permissions')) {
            return collect();
        }

        $overrides = RolePermission::query()
            ->where('role', $this->role->value)
            ->get(['permission', 'allowed']);

        return $overrides->mapWithKeys(fn (RolePermission $override) => [
            $override->permission => $override->allowed,
        ]);
    }

    private function resolveRole(mixed $value): UserRole
    {
        $role = UserRole::tryFrom((string) $value);

        if ($role) {
            return $role;
        }

        return match (strtolower(str_replace(['-', ' '], '_', (string) $value))) {
            'superadmin', 'super_admin', 'administrator' => UserRole::SuperAdmin,
            'kasir' => UserRole::Cashier,
            'gudang' => UserRole::Warehouse,
            'pelanggan' => UserRole::Customer,
            default => UserRole::Cashier,
        };
    }
}
