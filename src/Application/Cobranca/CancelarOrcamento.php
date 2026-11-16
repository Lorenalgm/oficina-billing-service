<?php

namespace Application\Cobranca;

use Domain\Cobranca\OrcamentoRepository;
use Domain\Shared\Messaging\Envelope;
use Domain\Shared\Messaging\MessageHandler;

class CancelarOrcamento implements MessageHandler
{
    public function __construct(
        private OrcamentoRepository $orcamentos,
        private Eventos $eventos,
    ) {}

    public function tratar(Envelope $comando): void
    {
        $orcamento = $this->orcamentos->buscarPorSaga($comando->sagaId);
        if ($orcamento !== null) {
            $orcamento->cancelar();
            $this->orcamentos->salvar($orcamento);
        }

        $this->eventos->responder($comando, 'billing.evt.orcamento-cancelado', ['orcamento_id' => $orcamento?->id]);
    }
}
