<?php

namespace Domain\Cobranca;

interface GatewayPagamento
{
    /**
     * @param array{nome: ?string, email: ?string, documento: ?string} $pagador
     * @return array{gateway_id: string, link: string}
     */
    public function criarCobranca(Pagamento $pagamento, array $pagador): array;

    public function estornar(Pagamento $pagamento): void;
}
