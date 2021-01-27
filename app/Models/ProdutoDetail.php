<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdutoDetail extends Model
{
    use HasFactory;

    public static $LIMIT = 20;

    protected $casts = [
        'ficha' => 'object'
    ];

    public function produtos()
    {
		return $this->hasMany(Produto::class);
    }

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class);
    }

    public function images()
    {
        return $this->hasMany(ProdutoImg::class);
    }

    public static function getParcelas($valor)
    {
        $parc = env('PARCELA_SEM_JUROS');
        while ($parc)
        {
            if (($valor / $parc) >= 5) return $parc;
            $parc --;
        }

        return 1;
    }

    public function getVezes()
    {
        return self::getParcelas($this->preco);
    }

    public function getPrecoSemJurosAttribute()
    {
        return number_format($this->preco / $this->getVezes(), 2, ',', '.');
    }

    public static function bestsellers($limit = 12)
    {
        return self::where('bestseller', 1)
        ->orderBy('created_at', 'DESC')
        ->limit($limit)
        ->get();
    }



    public static function destaque($limit = 12)
    {
        return self::where('destaque', 1)
        ->orderBy('created_at', 'DESC')
        ->limit($limit)
        ->get();
    }

    public function image($tamanho)
    {
        $img = $this->images()
        ->where('produto_imgs.img_size_id', $tamanho)
        ->orderBy('produto_imgs.id')
        ->first()->nome ?? '';
        return $img;
    }

    public function imgsVars()
    {
        return $this->images()
        ->select('produto_imgs.nome')
        ->whereNull('produto_imgs.produto_id')
        ->where('produto_imgs.img_size_id', 1)
        ->orderBy('produto_imgs.id')
        ->get();
    }
}
