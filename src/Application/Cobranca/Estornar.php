<?php

namespace Application\Cobranca;

use Domain\Cobranca\GatewayPagamento;
use Domain\Cobranca\PagamentoRepository;
use Domain\Cobranca\StatusPagamento;
use Domain\Shared\Messaging\Envelope;
use Domain\Shared\Messaging\MessageHandler;
use Throwable;

class Estornar implements MessageHandler
{
    public function __construct(
        private PagamentoRepository $pagamentos,
        private GatewayPagamento $gateway,
        private Eventos $eventos,
    ) {}

    public function tratar(Envelope $comando): void
    {
        $pagamento = $this->pagamentos->buscarPorSaga($comando->sagaId);

        if ($pagamento?->status() === StatusPagamento::Aprovado) {
            try {
                $this->gateway->estornar($pagamento);
            } catch (Throwable $e) {
                $this->eventos->responder($comando, 'billing.evt.estorno-falhou', [
                    'pagamento_id' => $pagamento->id,
                    'motivo' => $e->getMessage(),
                ]);

                return;
            }

            $pagamento->estornar();
            $this->pagamentos->salvar($pagamento);
        }

        $this->eventos->responder($comando, 'billing.evt.pagamento-estornado', [
            'pagamento_id' => $pagamento?->id,
            'estornado_em' => now()->toAtomString(),
        ]);
    }
}
