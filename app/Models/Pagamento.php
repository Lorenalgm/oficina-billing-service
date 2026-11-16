<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    protected $table = 'pagamentos';

    protected $fillable = ['saga_id', 'os_id', 'orcamento_id', 'valor', 'status', 'gateway_id', 'link'];

    protected $casts = ['os_id' => 'integer', 'orcamento_id' => 'integer', 'valor' => 'integer'];
}
