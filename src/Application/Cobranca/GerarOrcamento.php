<?php

namespace Application\Cobranca;

use Domain\Cobranca\Orcamento;
use Domain\Cobranca\OrcamentoInvalido;
use Domain\Cobranca\OrcamentoRepository;
use Domain\Shared\Messaging\Envelope;
use Domain\Shared\Messaging\MessageHandler;

class GerarOrcamento implements MessageHandler
{
    public function __construct(
        private OrcamentoRepository $orcamentos,
        private Eventos $eventos,
        private string $urlPublica,
    ) {}

    public function tratar(Envelope $comando): void
    {
        try {
            $orcamento = Orcamento::criar(
                $comando->sagaId,
                $comando->osId,
                $comando->payload['cliente'] ?? [],
                $comando->payload['itens'] ?? [],
            );
        } catch (OrcamentoInvalido $e) {
            $this->eventos->responder($comando, 'billing.evt.orcamento-falhou', ['motivo' => $e->getMessage()]);

            return;
        }

        $token = $orcamento->token();
        $orcamento = $this->orcamentos->salvar($orcamento);

        $this->eventos->responder($comando, 'billing.evt.orcamento-gerado', [
            'orcamento_id' => $orcamento->id,
            'valor_total' => $orcamento->valorTotal,
            'link_aprovacao' => rtrim($this->urlPublica, '/')."/api/orcamentos/{$orcamento->id}?token={$token}",
        ]);
    }
}
