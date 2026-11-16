<?php

namespace Domain\Cobranca;

interface OrcamentoRepository
{
    public function salvar(Orcamento $orcamento): Orcamento;

    public function buscar(int $id): ?Orcamento;

    public function buscarPorSaga(string $sagaId): ?Orcamento;
}
