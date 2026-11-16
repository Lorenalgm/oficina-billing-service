<?php

namespace App\Http\Controllers;

use App\Support\CorrelationContext;
use Application\Cobranca\ResultadoDoPagamento;
use Domain\Cobranca\PagamentoRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagamentoController extends Controller
{
    public function simular(Request $request, int $id, PagamentoRepository $pagamentos, ResultadoDoPagamento $resultado): JsonResponse
    {
        abort_unless(config('services.pagamento.gateway') === 'fake', 404);
        $dados = $request->validate(['resultado' => ['required', 'in:aprovado,recusado']]);

        $pagamento = DB::transaction(function () use ($id, $dados, $pagamentos, $resultado) {
            $pagamento = $pagamentos->buscar($id) ?? abort(404);

            return $resultado->registrar($pagamento, $dados['resultado'] === 'aprovado', CorrelationContext::getOrGenerate(), 'recusado na simulação');
        });

        return response()->json(['data' => ['id' => $pagamento->id, 'status' => $pagamento->status()->value]]);
    }
}
