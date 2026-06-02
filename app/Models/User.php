<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function roleLabel(): string
    {
        return $this->role->label();
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

        $this->permissionOverrideMap()->each(function (bool $allowed, string $permission) use (&$permissions) {
            $permissions = $allowed
                ? $permissions->push($permission)
                : $permissions->reject(fn (string $value) => $value === $permission);
        });

        return $permissions->unique()->values()->all();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === UserRole::SuperAdmin) {
            return true;
        }

        $override = $this->permissionOverrideMap()->get($permission);

        if ($override !== null) {
            return $override;
        }

        return $this->role->can($permission);
    }

    /**
     * @return Collection<string, bool>
     */
    private function permissionOverrideMap(): Collection
    {
        $overrides = $this->relationLoaded('permissionOverrides')
            ? $this->permissionOverrides
            : $this->permissionOverrides()->get(['permission', 'allowed']);

        return $overrides->mapWithKeys(fn (UserPermission $override) => [
            $override->permission => $override->allowed,
        ]);
    }
}
