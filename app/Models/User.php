<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
        'role_id',
        'tenant_id',
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
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function getRoleName(): string
    {
        return $this->roleModel?->name ?? $this->role ?? 'tenant';
    }

    public function isSuperAdmin(): bool
    {
        return $this->getRoleName() === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->getRoleName(), ['superadmin', 'admin']);
    }

    public function isTenant(): bool
    {
        return $this->getRoleName() === 'tenant';
    }

    public function hasRole(string|array $roles): bool
    {
        $currentRole = $this->getRoleName();
        $roles = (array) $roles;

        return in_array($currentRole, $roles);
    }

    public function hasAbility(string $ability): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->roleModel) {
            return $this->roleModel->hasAbility($ability);
        }

        // Fallback default capabilities if role_id not linked
        if ($this->isAdmin()) {
            return !str_starts_with($ability, 'role.');
        }

        if ($this->isTenant()) {
            return in_array($ability, ['sales.view_own', 'sales_report.view']);
        }

        return false;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
