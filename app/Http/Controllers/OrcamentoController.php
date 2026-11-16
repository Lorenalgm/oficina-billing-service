<?php

namespace App\Http\Controllers;

use App\Support\CorrelationContext;
use Application\Cobranca\RespostaDoCliente;
use Domain\Cobranca\Orcamento;
use Domain\Cobranca\TokenInvalido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrcamentoController extends Controller
{
    public function __construct(private RespostaDoCliente $resposta) {}

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->executar(fn () => $this->resposta->consultar($id, $this->token($request)));
    }

    public function aprovar(Request $request, int $id): JsonResponse
    {
        return $this->executar(fn () => $this->resposta->aprovar($id, $this->token($request), CorrelationContext::getOrGenerate()));
    }

    public function recusar(Request $request, int $id): JsonResponse
    {
        return $this->executar(fn () => $this->resposta->recusar($id, $this->token($request), CorrelationContext::getOrGenerate()));
    }

    private function executar(callable $acao): JsonResponse
    {
        try {
            /** @var Orcamento $orcamento */
            $orcamento = DB::transaction($acao);
        } catch (TokenInvalido $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        return response()->json(['data' => [
            'id' => $orcamento->id,
            'os_id' => $orcamento->osId,
            'status' => $orcamento->status()->value,
            'valor_total' => $orcamento->valorTotal,
            'itens' => $orcamento->itens,
        ]]);
    }

    private function token(Request $request): string
    {
        return (string) $request->input('token', '');
    }
}
