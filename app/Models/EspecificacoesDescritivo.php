<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EspecificacoesDescritivo extends Model
{
    use HasFactory;

    protected $fillable = ['nome'];

    public function especificacoes()
    {
        return $this->belongsToMany(
            EspecificacoesDescritivo::class,
            'espec_x_des',
            'espec_des_id',
            'especificacao_id'
        )
        ->using(EspecificacoesDescritivoRelations::class)
        ->withPivot(['valor', 'slug']);
    }
}
