<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\EspecificacoesDescritivo;
use App\Models\ProdutoDetail;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($slug)
    {
        $produto = ProdutoDetail::where('slug', $slug)->first();

        if ($produto)
        {
            $breadcrumbs = $produto->breadcrumb();
            $bcs = [];
            foreach ($breadcrumbs as $bc)
            {
                array_push($bcs, [
                    "name"  => $bc->nome,
                    "link"  => "categoria/" . $bc->slug
                ]);
            }

            $produto->img  = $produto->image(3);
            $produto->imgs = $produto->imgsVars();

            return response()->json([
                'data'      => [
                    'prod'          => $produto,
                    'breadcrumb'    => $bcs
                ],
                'message'   => 'success',
                'status'   => true,
            ]);
        }
        
        return response()->json([
            'data'      => null,
            'message'   => 'product not found',
            'status'    => false
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function bestseller(Request $request)
    {
        $p = ProdutoDetail::bestsellers($request->limit ?: 4);
        $p = $p->map(function($item) {
            $o = new \stdClass();
            $o->nome        = $item->nome;
            $o->slug        = $item->slug;
            $o->preco       = $item->preco;
            $o->image       = $item->image(2);
            $o->votos       = $item->votos;
            $o->total_votos = $item->total_votos;
            $o->promocao    = $item->promocao;
            $o->preco_fic   = $item->preco_fic;
            $o->semJuros = $item->getVezes() .'x R$' . $item->preco_sem_juros . ' sem juros';
            return $o;
        });

        return response()->json([
            'data'      => $p,
            'status'    => true
        ]);
    }

    public function getCategory($cat, Request $request)
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
                $o->slug        = $item->slug;
                $o->preco       = $item->preco;
                $o->image       = $item->image(2);
                $o->votos       = $item->votos;
                $o->total_votos = $item->total_votos;
                $o->promocao    = $item->promocao;
                $o->preco_fic   = $item->preco_fic;
                $o->semJuros = $item->getVezes() .'x R$' . $item->preco_sem_juros . ' sem juros';
                return $o;
            });

            $produtos = new \stdClass();
            $produtos->page     = $page;
            $produtos->items    = $items;
            $produtos->limit    = $limit;
            $produtos->total    = $total;

            return response()->json([
                'data'      => $produtos,
                'status'    => true
            ]);
        }

        return response()->json([
            'data'      => null,
            'message'   => 'category not found',
            'status'    => false
        ]);
    }

    public function busca(Request $request)
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


        // texto de pesquisa
        if ($filters->has('s'))
        {
            // conectores
            $conectores = file(base_path('config/__conectores.ini'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $request->s = preg_replace($conectores, '', $request->s);

            // negs
            $negs = file(base_path('config/__negs.ini'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $request->s = preg_replace($negs, '', $request->s);

            // sinonimos
            $sin = parse_ini_file(base_path('config/__sinonimos.ini'));
            $request->s = preg_replace(array_keys($sin), array_values($sin), $request->s);

            // keywords
            $keywords = parse_ini_file(base_path('config/__keywords.ini'));
            $keywordsSelected = [];

            // argumentos de pesquisa
            $args = preg_split('/\s+/', $request->s);

            // argumentos de pesquisa filtrados
            $search = array_filter($args, function($v) use ($keywords, &$keywordsSelected) {

                $i = 1;

                if (isset($keywords[$v]))
                {
                    if ($keywords[$v] == 'categoria' and !array_key_exists('categoria', $keywordsSelected))
                    {
                        $keywordsSelected['categoria'] = $v;
                    }
                    if ($keywords[$v] == 'marca' and !array_key_exists('marca', $keywordsSelected))
                    {
                        $keywordsSelected['marca'] = $v;
                    }

                    $i = 0;
                }

                return $i and $v;
            });

            // se há uma marca como palavra-chave
            if (isset($keywordsSelected['marca']) and !$filters->has('marca'))
            {
                $filters->put('marca', $keywordsSelected['marca']);
            }

            // define o texto de pesquisa
            $filters->put('s', $search);
        }


        // produtos
        $builder = ProdutoDetail::filterSemCategoria($filters);


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
            $o->slug        = $item->slug;
            $o->preco       = $item->preco;
            $o->image       = $item->image(2);
            $o->votos       = $item->votos;
            $o->total_votos = $item->total_votos;
            $o->promocao    = $item->promocao;
            $o->preco_fic   = $item->preco_fic;
            $o->semJuros = $item->getVezes() .'x R$' . $item->preco_sem_juros . ' sem juros';
            return $o;
        });

        $produtos = new \stdClass();
        $produtos->page     = $page;
        $produtos->items    = $items;
        $produtos->limit    = $limit;
        $produtos->total    = $total;


        return response()->json([
            'data'      => $produtos,
            'status'    => true
        ]);
    }
}
