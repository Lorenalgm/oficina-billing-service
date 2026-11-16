<?php

namespace Domain\Cobranca;

interface PagamentoRepository
{
    public function salvar(Pagamento $pagamento): Pagamento;

    public function buscar(int $id): ?Pagamento;

    public function buscarPorSaga(string $sagaId): ?Pagamento;

    public function buscarPorGatewayId(string $gatewayId): ?Pagamento;
}
