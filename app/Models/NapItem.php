<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NapItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'segment_name',
        'item_id',
        'quantity',
        'year',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
