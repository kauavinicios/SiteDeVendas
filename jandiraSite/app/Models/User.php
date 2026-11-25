<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Valores padrão em memória ao instanciar sem persistir.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => UserRole::Customer->value,
        'active' => true,
        'marketing_opt_in' => false,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'phone',
        'marketing_opt_in',
        'marketing_opt_in_at',
        'marketing_opt_out_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
            'role' => UserRole::class,
            'active' => 'boolean',
            'marketing_opt_in' => 'boolean',
            'marketing_opt_in_at' => 'datetime',
            'marketing_opt_out_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role?->isStaff() ?? false;
    }

    public function isCustomer(): bool
    {
        return ! $this->isStaff();
    }

    public function hasPermission(Permission|string $permission): bool
    {
        if (! $this->active || ! $this->role) {
            return false;
        }

        $target = $permission instanceof Permission
            ? $permission
            : Permission::tryFrom($permission);

        if (! $target) {
            return false;
        }

        return in_array($target, $this->role->permissions(), true);
    }

    /**
     * @return list<string>
     */
    public function permissionValues(): array
    {
        if (! $this->active || ! $this->role) {
            return [];
        }

        return array_map(
            fn (Permission $permission): string => $permission->value,
            $this->role->permissions(),
        );
    }
}
