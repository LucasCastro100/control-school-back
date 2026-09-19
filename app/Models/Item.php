<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'category',
        'naps',
    ];

    protected $casts = [
        'naps' => 'array',
    ];

    public function napItems(): HasMany
    {
        return $this->hasMany(NapItem::class);
    }
}
