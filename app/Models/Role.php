<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Check if role has a specific ability / permission.
     */
    public function hasAbility(string $ability): bool
    {
        return $this->permissions->contains('name', $ability);
    }

    /**
     * Insert/assign abilities into this role.
     */
    public function giveAbilities(array|string $abilities): self
    {
        $abilities = (array) $abilities;

        $permissionIds = Permission::whereIn('name', $abilities)->pluck('id');
        $this->permissions()->syncWithoutDetaching($permissionIds);

        // Reload relation
        $this->load('permissions');

        return $this;
    }
}
