<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nome',
        'descricao',
        'finalizado',
        'data_limite',
    ];

    protected $casts = [
        'finalizado' => 'boolean',
        'data_limite' => 'datetime',
    ];

    public function scopeFinalizados($query)
    {
        return $query->where('finalizado', true);
    }

    public function scopePendentes($query)
    {
        return $query->where('finalizado', false);
    }

    public function scopeVencidos($query)
    {
        return $query->where('data_limite', '<', now())
                     ->where('finalizado', false);
    }

    public function getVencidaAttribute()
    {
        if (!$this->data_limite || $this->finalizado) {
            return false;
        }

        return $this->data_limite->isPast();
    }

    public function getStatusAttribute()
    {
        if ($this->finalizado) {
            return 'Finalizada';
        }

        if ($this->vencida) {
            return 'Vencida';
        }

        return 'Pendente';
    }

}
