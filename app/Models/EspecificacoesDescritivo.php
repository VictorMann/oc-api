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

    public static function selectedFilter($items)
    {
        return self::select(
            'especificacoes_descritivos.nome',
            'especificacoes_descritivos.slug',
            'exd.valor',
            'exd.slug AS valorSlug'
        )
        ->join('espec_x_des AS exd', 'exd.espec_des_id', '=', 'especificacoes_descritivos.id')
        ->whereIn('especificacoes_descritivos.slug', $items[0])
        ->whereIn('exd.slug', $items[1])
        ->groupBy('especificacoes_descritivos.slug')
        ->get();


        // SELECT ed.nome, ed.slug, exd.valor, exd.slug AS valorSlug
        // FROM especificacoes_descritivos AS ed
        // INNER JOIN espec_x_des AS exd ON exd.espec_des_id = ed.id
        // WHERE ed.slug IN ('freio', 'material-quadro')
        // AND exd.slug IN ('v-break', 'aluminio')
    }
}
