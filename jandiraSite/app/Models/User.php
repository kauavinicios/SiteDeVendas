<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
        'terms_accepted_at',
        'terms_version',
        'privacy_accepted_at',
        'privacy_version',
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
            'terms_accepted_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
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

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function hasOrderHistory(): bool
    {
        return method_exists($this, 'orders') && $this->orders()->exists();
    }

    /**
     * Anonimiza os dados pessoais do cliente e aplica soft delete para preservar
     * a integridade contábil/fiscal do histórico de compras (LGPD Art. 16).
     */
    public function anonymizeAndSoftDelete(): void
    {
        if (! $this->isCustomer()) {
            throw new \LogicException('Contas internas da equipe não podem ser excluídas.');
        }

        $this->forceFill([
            'name' => 'Cliente excluído',
            'email' => 'deleted_'.$this->id.'_'.Str::lower(Str::random(10)).'@anonimizado.local',
            'phone' => null,
            'password' => Hash::make(Str::random(40)),
            'remember_token' => null,
            'email_verified_at' => null,
            'active' => false,
            'marketing_opt_in' => false,
            'marketing_opt_out_at' => now(),
        ])->save();

        $this->delete();
    }

    /**
     * Encerra a conta do cliente: exclui definitivamente se não houver pedidos
     * vinculados, ou anonimiza com soft delete quando houver histórico de compras.
     */
    public function closeCustomerAccount(): void
    {
        if (! $this->isCustomer()) {
            throw new \LogicException('Contas internas da equipe não podem ser excluídas.');
        }

        if ($this->hasOrderHistory()) {
            $this->anonymizeAndSoftDelete();

            return;
        }

        $this->forceDelete();
    }
}
