<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = [
        'produto_detail_id',
        'codigo',
        'sku',
        'qtd',
        'espec',
    ];

    public function produto_detail()
    {
        return $this->belongsTo(ProdutoDetail::class);
    }

    public function imgs()
    {
        return $this->hasMany(ProdutoImg::class);
    }

    public function image($tamanho = 1)
    {
        $img = $this->imgs()
        ->where('produto_imgs.img_size_id', $tamanho)
        ->orderBy('produto_imgs.id')
        ->first()->nome ?? null;

        return $img ?: $this->produto_detail->image($tamanho);
    }

    // public function options()
    // {
    //     return $this->belongsToMany(Option::class)
    //     ->withPivot(['valor']);
    // }

    // public function pedidos()
    // {
    //     return $this->belongsToMany(Pedido::class)
    //     ->withPivot(['quantidade']);
    // }
}
