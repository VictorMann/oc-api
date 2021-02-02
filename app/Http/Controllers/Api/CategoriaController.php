<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\EspecificacoesDescritivo;
use App\Models\Marca;
use App\Models\ProdutoDetail;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function show(Request $request, $cat)
    {
        $builder = Categoria::select('id', 'sub', 'nome', 'slug');

        // caso seja um ID
        if (intval($cat))
        {
            $categoria = $builder->find($cat);
        }
        // caso seja um slug
        else
        {
            $categoria = $builder->where('slug', $cat)->first();
        }

        if ($categoria && $request->products)
        {
            $filters = collect();


            $builder = $categoria->produtosFamily($filters);


            $total = $builder->get()->count();
            $limit = (int) ($request->limit > 0 ? $request->limit : ProdutoDetail::$LIMIT);
            $page  = (int) ($request->page > 0 ? $request->page : 1);

            $offset = ($page - 1) * $limit;

            $items = $builder
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function($item) {
                $o = new \stdClass();
                $o->nome        = $item->nome;
                $o->preco       = $item->preco;
                $o->image       = $item->image(2);
                $o->votos       = $item->votos;
                $o->total_votos = $item->total_votos;
                $o->semJuros = $item->getVezes() .'x R$' . $item->preco_sem_juros . ' sem juros';
                return $o;
            });

            $produtos = new \stdClass();
            $produtos->page     = $page;
            $produtos->items    = $items;
            $produtos->limit    = $limit;
            $produtos->total    = $total;

            $categoria->produtos = $produtos;
        }


        return response()->json([
            'data'      => $categoria,
            'status'    => true
        ]);
    }

    public function sideFilter(Request $request, $cat)
    {
        // caso seja um ID
        if (intval($cat))
        {
            $categoria = Categoria::find($cat);
        }
        // caso seja um slug
        else
        {
            $categoria = Categoria::where('slug', $cat)->first();
        }


        if ($categoria)
        {
            // obtem os filtros já como uma collection
            $filters = collect( $request->except(['page']) )
            ->filter(function($f) {
                return $f and is_string($f);
            });


            // se houver precos
            if ($filters->has('preco'))
            {
                if (preg_match('/^(\d+)\-(\d+)$/', $filters->get('preco'), $match))
                    $filters->put('preco', [$match[1], $match[2]]);
                else
                    $filters->forget('preco');
            }



            // Especificações
            $espec = EspecificacoesDescritivo::select('slug')->get();
            $espec = $filters
            ->filter(function($v, $k) use ($espec) {
                return $espec->where('slug', $k)->first();
            })
            ->map(function($v, $k) {
                return $k . '::' . $v;
            })
            ->values()
            ->toArray();

            if (count($espec)) $filters->put('espec', $espec);

            $items = $ed = $exd = [];

            foreach ($espec as $v) {
                $v = explode('::' ,$v);
                array_push($ed, $v[0]);
                array_push($exd, $v[1]);
            }

            $items[0] = $ed;
            $items[1] = $exd;

            $ff = EspecificacoesDescritivo::selectedFilter($items);

            // filtros selecionados
            $selected = collect();

            $side_filters = collect();

            // total itens por categorias subs
            $sf = $categoria->filterFamilyTotalSubs($filters);
            if ($qtd = $sf->count())
            {
                if (!($qtd == 1 && $sf->first()->val == $categoria->nome))
                {
                    $side_filters->push((object) [
                        'nome'      => 'Categorias',
                        'slug'      => 'categoria',
                        'values'    => $sf
                    ]);
                }
            }

            // Filtro lateral : [ Marca ]
            if (!$filters->has('marca'))
            {
                // total itens por marcas subs
                $sf = $categoria->filterFamilyTotalMarcas($filters);
                $side_filters->push((object) [
                    'nome'      => 'Marcas',
                    'slug'      => 'marca',
                    'values'    => $sf
                ]);
            }
            else
            {
                $marca = Marca::where('slug', $filters->get('marca'))->first();
                if ($marca)
                {
                    $selectFilter = new \StdClass();
                    $selectFilter->nome = 'Marca';
                    $selectFilter->valor = $marca->nome;
                    $selectFilter->slug = 'marca';
                    $selected->push($selectFilter);
                }
            }

            if ($ff->count())
            {
                foreach ($ff as $item) {
                    $selectFilter = new \StdClass();
                    $selectFilter->nome = $item->nome;
                    $selectFilter->valor = $item->valor;
                    $selectFilter->slug = $item->slug;
                    $selected->push($selectFilter);
                }
            }


            // Filtro Lateral de acordo com a categoria
            $sf = $categoria->sideFilterComCategoria($filters)
            ->reduce(function($carry, $item) {

                $newVal = true;

                if ($carry)
                {
                    if ($o = $carry->where('slug', $item->slug)->first())
                    {
                        $newVal = false;

                        array_push($o->values, (object) [
                            'nome'  => $item->valor,
                            'slug'  => $item->valorSlug,
                            'qtd'   => $item->qtd
                        ]);
                    }
                }
                else $carry = collect();


                if ($newVal)
                {
                    $carry->push(
                        (object) [
                            'nome' => $item->nome,
                            'slug' => $item->slug,
                            'values' => [
                                (object) [
                                    'nome'  => $item->valor,
                                    'slug'  => $item->valorSlug,
                                    'qtd'   => $item->qtd
                                ]
                            ]
                        ]
                    );
                }

                return $carry;
            });


            if ($sf) $side_filters = $side_filters->concat($sf);


            // Filtro lateral : [ Preco e Avalição ]
            if ( !$filters->has('preco') or !$filters->has('votos') )
            {
                // filters Preco e Votos
                $sf = $categoria->filterFamilyPrecoVotos($filters);

                if (!$filters->has('preco'))
                {
                    $side_filters->push((object) [
                        'nome'      => 'Preço',
                        'slug'      => 'preco',
                        'values'    => (object) [
                            'min'   => $sf->pmin,
                            'max'   => $sf->pmax
                        ]
                    ]);
                }

                if (!$filters->has('votos'))
                {
                    $values = [];
                    if ($sf->v1) array_push($values, (object) ['val' => 1, 'qtd' => $sf->v1]);
                    if ($sf->v2) array_push($values, (object) ['val' => 2, 'qtd' => $sf->v2]);
                    if ($sf->v3) array_push($values, (object) ['val' => 3, 'qtd' => $sf->v3]);
                    if ($sf->v4) array_push($values, (object) ['val' => 4, 'qtd' => $sf->v4]);
                    if ($sf->v5) array_push($values, (object) ['val' => 5, 'qtd' => $sf->v5]);

                    if (count($values))
                    {
                        $side_filters->push((object) [
                            'nome'      => 'Avaliação',
                            'slug'      => 'votos',
                            'values'    => $values
                        ]);
                    }
                }
            }

            if ($filters->has('preco'))
            {
                $selectFilter = new \StdClass();
                $selectFilter->nome = 'Preço';
                $selectFilter->valor = implode('-', $filters->get('preco'));
                $selectFilter->slug = 'preco';
                $selected->push($selectFilter);
            }



            $data = new \StdClass();
            $data->filterList   = $side_filters;
            $data->selected     = $selected;

            return response()->json([
                'data'      => $data,
                'status'    => true
            ]);
        }

        return response()->json([
            'data'      => null,
            'message'   => 'category not found',
            'status'    => false
        ]);
    }
}
