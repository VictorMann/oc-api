<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EspecificacoesDescritivoRelations extends Pivot
{
    protected $table = 'espec_x_des';
    public $timestamps = false;

    protected $fillable = [
        'especificacao_id',
        'espec_des_id',
        'valor',
        'slug',
    ];

    public function descritivo()
    {
        return $this->hasOne(EspecificacoesDescritivo::class, 'id', 'espec_des_id');
    }

    public function espec()
    {
        return $this->hasOne(Especificacoes::class, 'id', 'especificacao_id');
    }
}
