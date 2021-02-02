<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    /**
     *  FILTER
     *  total itens por categorias subs de um pai
     */
    public function filterFamilyTotalSubs($filters)
    {
        $builder = self::select(
            'categorias.nome AS nome',
            'categorias.slug AS slug',
            DB::raw('COUNT(DISTINCT pd.id) AS qtd'))
        ->join('categoria_produto_detail AS cd', 'cd.categoria_id', '=', 'categorias.id')
        ->join('produto_details AS pd', 'pd.id', '=', 'cd.produto_detail_id')
        ->join('marcas AS m', 'm.id', '=', 'pd.marca_id')
        ->join('produtos AS p', 'p.produto_detail_id', '=', 'pd.id')
        ->whereNull('pd.deleted_at');

        // filtro categoria family
        $builder->whereIn('categorias.id', $this->idsSubsComProdutos());

        if ($filters->count())
        {
            if ($filters->has('marca'))
            {
                $builder->where('m.slug', $filters->get('marca'));
            }

            if ($filters->has('votos'))
            {
                $builder->where('pd.votos', $filters->get('votos'));
            }

            if ($filters->has('preco'))
            {
                $builder->whereBetween('pd.preco', $filters->get('preco'));
            }

            if ($filters->has('espec') and is_array($filters->get('espec')))
            {
                foreach ($filters->get('espec') as $espec)
                    $builder->where('p.espec', 'LIKE', "%{$espec}%");
            }

            if ($filters->has('s') and is_array($filters->get('s')))
            {
                foreach ($filters->get('s') as $f)
                    $builder->where('pd.nome', 'LIKE', "%{$f}%");
            }
        }

        return $builder
        ->groupBy('categorias.slug')
        ->get();
    }

    /**
     *  FILTER
     *  total itens por marca de cat subs de um pai
     */
    public function filterFamilyTotalMarcas($filters)
    {
        $builder = self::select(
            'm.nome AS nome',
            'm.slug AS slug',
            DB::raw('COUNT(DISTINCT pd.id) AS qtd'))
        ->join('categoria_produto_detail AS cd', 'categorias.id', '=', 'cd.categoria_id')
        ->join('produto_details AS pd', 'cd.produto_detail_id', '=', 'pd.id')
        ->join('marcas AS m', 'pd.marca_id', '=', 'm.id')
        ->join('produtos AS p', 'p.produto_detail_id', '=', 'pd.id')
        ->whereNull('pd.deleted_at');


        // filtro categoria family
        $builder->whereIn('categorias.id', $this->idsSubsComProdutos());


        if ($filters->count())
        {
            if ($filters->has('marca'))
            {
                $builder->where('m.slug', $filters->get('marca'));
            }

            if ($filters->has('votos'))
            {
                $builder->where('pd.votos', $filters->get('votos'));
            }

            if ($filters->has('preco'))
            {
                $builder->whereBetween('pd.preco', $filters->get('preco'));
            }

            if ($filters->has('espec') and is_array($filters->get('espec')))
            {
                foreach ($filters->get('espec') as $espec)
                    $builder->where('p.espec', 'LIKE', "%{$espec}%");
            }

            if ($filters->has('s') and is_array($filters->get('s')))
            {
                foreach ($filters->get('s') as $f)
                    $builder->where('pd.nome', 'LIKE', "%{$f}%");
            }
        }

        return $builder
        ->groupBy('m.slug')
        ->get();
    }

    /**
     *  FILTERS
     *  @param Collection $filters
     */
    public function sideFilterComCategoria($filters)
    {
        $builder = self::select(
            'esd.nome',
            'esd.slug',
            'ed.valor',
            'ed.slug AS valorSlug',
            DB::raw('COUNT(DISTINCT pd.id) AS qtd'))
        ->join('espec_x_categoria AS ec', 'categorias.id', '=', 'ec.categoria_id')
        ->join('espec_x_des AS ed', 'ec.especificacao_id', '=', 'ed.especificacao_id')
        ->join('especificacoes_descritivos AS esd', 'ed.espec_des_id', '=', 'esd.id')
        ->join('categoria_produto_detail AS cd', 'cd.categoria_id', '=', 'categorias.id')
        ->join('produto_details AS pd', 'pd.id', '=', 'cd.produto_detail_id')
        ->join('marcas AS m', 'm.id', '=', 'pd.marca_id')
        ->join('produtos AS p', function ($join) {
            $join->on('p.produto_detail_id', '=', 'pd.id')
            ->where('p.espec', 'LIKE', DB::raw('CONCAT_WS("::", CONCAT("%", esd.slug), CONCAT(ed.slug, "%"))'));
        })
        ->whereNull('pd.deleted_at');

        // filtro categoria family
        $builder->whereIn('categorias.id', $this->idsSubsComProdutos());

        if ($filters->count())
        {
            if ($filters->has('marca'))
            {
                $builder->where('m.slug', $filters->get('marca'));
            }

            if ($filters->has('votos'))
            {
                $builder->where('pd.votos', $filters->get('votos'));
            }

            if ($filters->has('preco'))
            {
                $builder->whereBetween('pd.preco', $filters->get('preco'));
            }

            if ($filters->has('espec') and is_array($filters->get('espec')))
            {
                foreach ($filters->get('espec') as $espec)
                    $builder->where('p.espec', 'LIKE', "%{$espec}%");
            }

            if ($filters->has('s') and is_array($filters->get('s')))
            {
                foreach ($filters->get('s') as $f)
                    $builder->where('pd.nome', 'LIKE', "%{$f}%");
            }
        }


        return $builder
        ->groupBy('ed.slug')
        ->havingRaw('LENGTH(ed.valor)')
        ->orderBy('esd.slug')
        ->orderBy('ed.slug')
        ->get();
    }

    /**
     *  FILTER
     *  Total itens por votos e range de preco de subs de uma cat pai
     *  @param Collection $filters
     */
    public function filterFamilyPrecoVotos($filters)
    {
        $builder = self::select(
            DB::raw('FLOOR(MIN(pd.preco)) AS pmin'),
            DB::raw('CEIL(MAX(pd.preco)) AS pmax'),
            DB::raw('SUM(IF(pd.votos = 1, 1, 0)) AS `v1`'),
            DB::raw('SUM(IF(pd.votos = 2, 1, 0)) AS `v2`'),
            DB::raw('SUM(IF(pd.votos = 3, 1, 0)) AS `v3`'),
            DB::raw('SUM(IF(pd.votos = 4, 1, 0)) AS `v4`'),
            DB::raw('SUM(IF(pd.votos = 5, 1, 0)) AS `v5`'))
        ->join('categoria_produto_detail AS cd', 'categorias.id', '=', 'cd.categoria_id')
        ->join('produto_details AS pd', 'cd.produto_detail_id', '=', 'pd.id')
        ->join('marcas AS m', 'm.id', '=', 'pd.marca_id')
        ->whereNull('pd.deleted_at');


        // filtro categoria
        $builder->whereIn('categorias.id', $this->idsSubsComProdutos());

        if ($filters->count())
        {
            if ($filters->has('marca'))
            {
                $builder->where('m.slug', $filters->get('marca'));
            }

            if ($filters->has('votos'))
            {
                $builder->where('pd.votos', $filters->get('votos'));
            }

            if ($filters->has('preco'))
            {
                $builder->whereBetween('pd.preco', $filters->get('preco'));
            }

            if ($filters->has('espec') and is_array($filters->get('espec')))
            {
                $builder2 = Produto::select('produto_detail_id');
                foreach ($filters->get('espec') as $espec) $builder2->where('espec', 'LIKE', "%{$espec}%");
                $builder2->groupBy('produto_detail_id');

                $builder->whereIn('pd.id', $builder2);
            }

            if ($filters->has('s') and is_array($filters->get('s')))
            {
                foreach ($filters->get('s') as $f)
                    $builder->where('pd.nome', 'LIKE', "%{$f}%");
            }
        }


        return $builder->first();
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
