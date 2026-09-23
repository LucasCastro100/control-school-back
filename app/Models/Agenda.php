<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agenda extends Model
{
    use HasUuids;

    protected $table = 'agenda';

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'activity',
        'registrar_mundoz',
        'escola',
        'ano',
        'tipo',
        'confirmado_por',
    ];

    public function orientadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'agenda_orientadores', 'agenda_id', 'orientador_id');
    }
}
