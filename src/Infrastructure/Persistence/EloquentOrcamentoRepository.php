<?php

namespace Infrastructure\Persistence;

use App\Models\Orcamento as Model;
use Domain\Cobranca\Orcamento;
use Domain\Cobranca\OrcamentoRepository;
use Domain\Cobranca\StatusOrcamento;

class EloquentOrcamentoRepository implements OrcamentoRepository
{
    public function salvar(Orcamento $orcamento): Orcamento
    {
        $model = Model::updateOrCreate(['saga_id' => $orcamento->sagaId], [
            'os_id' => $orcamento->osId,
            'cliente' => $orcamento->cliente,
            'itens' => $orcamento->itens,
            'valor_total' => $orcamento->valorTotal,
            'status' => $orcamento->status()->value,
            'token' => $orcamento->token(),
        ]);

        return $this->paraEntidade($model);
    }

    public function buscar(int $id): ?Orcamento
    {
        $model = Model::lockForUpdate()->find($id);

        return $model ? $this->paraEntidade($model) : null;
    }

    public function buscarPorSaga(string $sagaId): ?Orcamento
    {
        $model = Model::lockForUpdate()->where('saga_id', $sagaId)->first();

        return $model ? $this->paraEntidade($model) : null;
    }

    private function paraEntidade(Model $model): Orcamento
    {
        return new Orcamento(
            $model->id,
            $model->saga_id,
            $model->os_id,
            $model->cliente,
            $model->itens,
            $model->valor_total,
            StatusOrcamento::from($model->status),
            $model->token,
        );
    }
}
