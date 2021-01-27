<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdutoImg extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'produto_detail_id',
        'produto_id',
        'img_size_id',
        'nome'
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }

    public function detail()
    {
        return $this->belongsTo(ProdutoDetail::class);
    }
}
