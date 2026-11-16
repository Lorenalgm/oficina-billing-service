<?php

namespace Infrastructure\Persistence;

use App\Models\Pagamento as Model;
use Domain\Cobranca\Pagamento;
use Domain\Cobranca\PagamentoRepository;
use Domain\Cobranca\StatusPagamento;

class EloquentPagamentoRepository implements PagamentoRepository
{
    public function salvar(Pagamento $pagamento): Pagamento
    {
        $model = Model::updateOrCreate(['saga_id' => $pagamento->sagaId], [
            'os_id' => $pagamento->osId,
            'orcamento_id' => $pagamento->orcamentoId,
            'valor' => $pagamento->valor,
            'status' => $pagamento->status()->value,
            'gateway_id' => $pagamento->gatewayId(),
            'link' => $pagamento->link(),
        ]);

        return $this->paraEntidade($model);
    }

    public function buscar(int $id): ?Pagamento
    {
        return $this->primeiro(Model::whereKey($id));
    }

    public function buscarPorSaga(string $sagaId): ?Pagamento
    {
        return $this->primeiro(Model::where('saga_id', $sagaId));
    }

    public function buscarPorGatewayId(string $gatewayId): ?Pagamento
    {
        return $this->primeiro(Model::where('gateway_id', $gatewayId));
    }

    private function primeiro($query): ?Pagamento
    {
        $model = $query->lockForUpdate()->first();

        return $model ? $this->paraEntidade($model) : null;
    }

    private function paraEntidade(Model $model): Pagamento
    {
        return new Pagamento(
            $model->id,
            $model->saga_id,
            $model->os_id,
            $model->orcamento_id,
            $model->valor,
            StatusPagamento::from($model->status),
            $model->gateway_id,
            $model->link,
        );
    }
}
