<?php

namespace Infrastructure\Pagamento;

use Domain\Cobranca\GatewayPagamento;
use Domain\Cobranca\Pagamento;
use RuntimeException;

class FakeGateway implements GatewayPagamento
{
    public function __construct(
        private string $urlPublica,
        private bool $falharEstorno = false,
    ) {}

    public function criarCobranca(Pagamento $pagamento, array $pagador): array
    {
        return [
            'gateway_id' => "fake-{$pagamento->id}",
            'link' => rtrim($this->urlPublica, '/')."/api/pagamentos/{$pagamento->id}/simular",
        ];
    }

    public function estornar(Pagamento $pagamento): void
    {
        if ($this->falharEstorno) {
            throw new RuntimeException('Estorno indisponível no gateway fake.');
        }
    }
}
