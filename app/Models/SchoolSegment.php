<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSegment extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'segment_name',
        'year',
        'material_type',
        'schedule_type',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}