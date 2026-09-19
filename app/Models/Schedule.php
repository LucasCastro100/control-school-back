<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'class_id',
        'room_id',
        'day_of_week',
        'start_time',
        'end_time',
        'subject',
        'teacher',
        'fortnight',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'fortnight' => 'integer',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
