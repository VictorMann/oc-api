<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Especificacoes extends Model
{
    use HasFactory;

    protected $fillable = ['nome'];

    public function descritivos()
    {
        return $this->belongsToMany(
            EspecificacoesDescritivo::class,
            'espec_x_des',
            'especificacao_id',
            'espec_des_id'
        )
        ->using(EspecificacoesDescritivoRelations::class)
        ->withPivot(['valor']);
    }

    public function categorias()
    {
        return $this->belongsToMany(
            Categoria::class,
            'espec_x_categoria',
            'especificacao_id',
            'categoria_id'
        );
    }

    public function getDescritivosDistintosAttribute()
    {
        return $this->descritivos()->get()
        ->unique('id')
        ->values();
    }

    public function getDescritivosVals()
    {
        $data = collect();
        $this->descritivos()->orderBy('espec_x_des.id')->get()->each(function($d) use (&$data) {
            if (!$data->has($d->id)) {
                $v = collect([
                    'descritivo' => ['id' => $d->id, 'nome' => $d->nome],
                    'valores'    => collect()
                ]);

                $data->put($d->id, $v);
            }

            $data->get($d->id)->get('valores')->push($d->pivot->valor);
        });

        return $data->values();
    }
}
