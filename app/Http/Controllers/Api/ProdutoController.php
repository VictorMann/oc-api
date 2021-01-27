<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\ProdutoDetail;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
}
