<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    public function subs()
    {
        return $this->hasMany(self::class, 'sub');
    }

    public function pai()
    {
        return $this->belongsTo(self::class, 'sub');
    }

    public function produtos()
    {
        return $this->belongsToMany(ProdutoDetail::class);
    }

    public function produtosFamily($filters)
    {
        $builder = ProdutoDetail::select('produto_details.*')
        ->join('categoria_produto_detail AS cd', 'cd.produto_detail_id', '=', 'produto_details.id')
        ->join('categorias AS c', 'c.id', '=', 'cd.categoria_id')
        ->join('produtos AS p', 'p.produto_detail_id', '=', 'produto_details.id')
        ->join('marcas AS m', 'm.id', '=', 'produto_details.marca_id');

        // filtro categoria family
        $builder->whereIn('c.id', $this->idsSubsComProdutos());


        if ($filters->count())
        {
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

            if ($filters->has('espec') and is_array($filters->get('espec')))
            {
                foreach ($filters->get('espec') as $espec)
                    $builder->where('p.espec', 'LIKE', "%{$espec}%");
            }

            if ($filters->has('s') and is_array($filters->get('s')))
            {
                foreach ($filters->get('s') as $f)
                    $builder->where('produto_details.nome', 'LIKE', "%{$f}%");
            }

            // order
            if ($filters->has('o') and $o = $this->auxOrder($filters->get('o')))
            {
                $builder->orderBy($o[0], $o[1]);
            }
        }

        // ordem default
        if (!$filters->has('o') and !$this->auxOrder($filters->get('o')))
        {
            $builder->orderBy('produto_details.created_at', 'DESC');
        }


        return $builder->groupBy('produto_details.id');
    }

    private function auxOrder($order)
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

    /**
     *  PRIVADOS -- AUXILIAR
     */
    public function idsSubsComProdutos()
    {
        $ids = [];
        // recursão
        $f = function ($cat) use (&$f, &$ids) {

            if (isset($cat->subs))
            {
                if ($cat->produtos()->count()) array_push($ids, $cat->id);
                return $cat->subs->count() ? $f($cat->subs) : 0;
            }

            foreach ($cat as $c) $f($c);
        };

        $f($this);

        return $ids;
    }
}
