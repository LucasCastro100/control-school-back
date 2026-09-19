<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SegmentConfig extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'segment_name',
        'tapetes',
        'kits',
        'year',
    ];

    protected $casts = [
        'tapetes' => 'integer',
        'kits' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
