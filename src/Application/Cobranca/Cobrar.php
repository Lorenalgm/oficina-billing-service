<?php

namespace Application\Cobranca;

use Domain\Cobranca\GatewayPagamento;
use Domain\Cobranca\Pagamento;
use Domain\Cobranca\PagamentoRepository;
use Domain\Shared\Messaging\Envelope;
use Domain\Shared\Messaging\MessageHandler;

class Cobrar implements MessageHandler
{
    public function __construct(
        private PagamentoRepository $pagamentos,
        private GatewayPagamento $gateway,
        private Eventos $eventos,
    ) {}

    public function tratar(Envelope $comando): void
    {
        $pagamento = $this->pagamentos->buscarPorSaga($comando->sagaId)
            ?? $this->pagamentos->salvar(Pagamento::criar(
                $comando->sagaId,
                $comando->osId,
                (int) $comando->payload['orcamento_id'],
                (int) $comando->payload['valor'],
            ));

        if ($pagamento->link() === null) {
            $cobranca = $this->gateway->criarCobranca($pagamento, $comando->payload['pagador'] ?? []);
            $pagamento->registrarCobranca($cobranca['gateway_id'], $cobranca['link']);
            $pagamento = $this->pagamentos->salvar($pagamento);
        }

        $this->eventos->responder($comando, 'billing.evt.cobranca-criada', [
            'pagamento_id' => $pagamento->id,
            'link_pagamento' => $pagamento->link(),
        ]);
    }
}
