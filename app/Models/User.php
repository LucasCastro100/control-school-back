<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'role_id'])]
#[Hidden(['password', 'remember_token', 'role_model'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    public const PERMISSIONS = [
        'schools',
        'users',
        'roles',
        'items',
        'tbr',
        'all_schedules',
        'agenda',
    ];

    protected $appends = ['permissions', 'role_data'];

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

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'user_schools')->withPivot('nap');
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    protected function getRoleDataAttribute(): ?array
    {
        if (! $this->relationLoaded('roleModel')) {
            return null;
        }

        $role = $this->getRelation('roleModel');

        return $role ? $role->only(['id', 'name', 'permissions']) : null;
    }

    public function orientadorSchedules(): HasMany
    {
        return $this->hasMany(OrientadorSchedule::class, 'orientador_id');
    }

    public function agendaItems(): BelongsToMany
    {
        return $this->belongsToMany(Agenda::class, 'agenda_orientadores', 'orientador_id', 'agenda_id');
    }

    protected function getPermissionsAttribute(): array
    {
        if ($this->role === 'admin') {
            return self::PERMISSIONS;
        }

        if ($this->role_id && $this->relationLoaded('roleModel')) {
            $perms = $this->getRelation('roleModel')?->permissions;
            if (is_array($perms)) {
                return $perms;
            }
        }

        return match ($this->role) {
            'orientador' => ['schools', 'agenda'],
            'escola' => ['schools'],
            'professor' => ['agenda'],
            default => [],
        };
    }
}
