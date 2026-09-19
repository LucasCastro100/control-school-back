<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaOrientador extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'agenda_id',
        'orientador_id',
    ];

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function orientador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'orientador_id');
    }
}
