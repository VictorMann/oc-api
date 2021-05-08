<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    /**
     *      FILTERS
     *  @param Collection $filters
     */
    public static function filterSemCategoria($filters)
    {
        // query base
        $builder = self::select(
            DB::raw('produto_details.*'))
        ->join('marcas AS m', 'm.id', '=', 'produto_details.marca_id')
        ->whereNull('produto_details.deleted_at');

        // filtros

        if ($filters->has('marca'))
        {
            $builder->where('m.slug', $filters->get('marca'));
        }

        if ($filters->has('votos'))
        {
            $builder->where('produto_details.votos', $filters->get('votos'));
        }

        if ($filters->has('preco'))
        {
            $builder->whereBetween('produto_details.preco', $filters->get('preco'));
        }

        if ($filters->has('s') and is_array($filters->get('s')))
        {
            foreach ($filters->get('s') as $f)
                $builder->where('produto_details.nome', 'LIKE', "%{$f}%");
        }

        // order
        if ($filters->has('o') and $o = self::auxOrder($filters->get('o')))
        {
            $builder->orderBy($o[0], $o[1]);
        }
        // ordem default
        else
        {
            $builder->orderBy('produto_details.created_at', 'DESC');
        }

        return $builder;
    }

    public static function auxOrder($order)
    {
        if (strlen($order) <> 3) return;

        switch ($order)
        {
            case 'asc':
                return ['produto_details.preco', 'ASC'];
            case 'des':
                return ['produto_details.preco', 'DESC'];
            case 'rel':
                return ['produto_details.promocao', 'DESC'];
            case 'ven':
                return ['produto_details.promocao', 'DESC'];
            case 'ava':
                return ['produto_details.promocao', 'DESC'];
        }
    }

    public function breadcrumb()
    {
        // lista de categorias
        $breadcrumb = [];

        $categoria = $this->categorias->first();
        do array_unshift($breadcrumb, $categoria); while ($categoria = $categoria->pai);

        return $breadcrumb;
    }
}
