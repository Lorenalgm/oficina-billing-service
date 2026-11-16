<?php

namespace Application\Cobranca;

use Domain\Cobranca\Pagamento;
use Domain\Cobranca\PagamentoRepository;
use Domain\Cobranca\StatusPagamento;

class ResultadoDoPagamento
{
    public function __construct(
        private PagamentoRepository $pagamentos,
        private Eventos $eventos,
    ) {}

    public function registrar(Pagamento $pagamento, bool $aprovado, string $correlationId, ?string $motivo = null): Pagamento
    {
        if ($pagamento->status() !== StatusPagamento::Pendente) {
            return $pagamento;
        }

        $aprovado ? $pagamento->aprovar() : $pagamento->recusar();
        $pagamento = $this->pagamentos->salvar($pagamento);

        $this->eventos->publicar(
            $aprovado ? 'billing.evt.pagamento-confirmado' : 'billing.evt.pagamento-recusado',
            $pagamento->sagaId,
            $correlationId,
            $pagamento->osId,
            $aprovado
                ? ['pagamento_id' => $pagamento->id, 'valor' => $pagamento->valor, 'pago_em' => now()->toAtomString(), 'gateway_id' => $pagamento->gatewayId()]
                : ['pagamento_id' => $pagamento->id, 'motivo' => $motivo ?? 'pagamento recusado'],
        );

        return $pagamento;
    }
}
