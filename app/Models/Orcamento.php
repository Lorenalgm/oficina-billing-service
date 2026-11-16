<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orcamento extends Model
{
    protected $table = 'orcamentos';

    protected $fillable = ['saga_id', 'os_id', 'cliente', 'itens', 'valor_total', 'status', 'token'];

    protected $casts = [
        'os_id' => 'integer',
        'cliente' => 'array',
        'itens' => 'array',
        'valor_total' => 'integer',
    ];
}
