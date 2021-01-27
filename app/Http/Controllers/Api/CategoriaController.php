<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\ProdutoDetail;
use Illuminate\Http\Request;

class CategoriaController extends Controller
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
     * @param  int|string  $val
     * @return \Illuminate\Http\Response
     */
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
}
