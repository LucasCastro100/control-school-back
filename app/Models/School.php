<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'address',
        'region',
        'state',
        'city',
        'color',
        'email',
        'password',
        'schedule_type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'school_id');
    }

    public function orientadorSchedules(): HasMany
    {
        return $this->hasMany(OrientadorSchedule::class);
    }

    public function segmentConfigs(): HasMany
    {
        return $this->hasMany(SegmentConfig::class);
    }

    public function napItems(): HasMany
    {
        return $this->hasMany(NapItem::class);
    }

    public function tbrTeams(): HasMany
    {
        return $this->hasMany(TbrTeam::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_schools')->withPivot('nap');
    }
}
