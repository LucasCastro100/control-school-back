<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrientadorSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'orientador_id',
        'day_of_week',
        'start_time',
        'end_time',
        'activity',
        'year',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function orientador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'orientador_id');
    }
}
