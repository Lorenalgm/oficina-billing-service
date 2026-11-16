<?php

namespace Application\Cobranca;

use Domain\Cobranca\Orcamento;
use Domain\Cobranca\OrcamentoRepository;
use Domain\Cobranca\TokenInvalido;

class RespostaDoCliente
{
    public function __construct(
        private OrcamentoRepository $orcamentos,
        private Eventos $eventos,
    ) {}

    public function consultar(int $id, string $token): Orcamento
    {
        $orcamento = $this->orcamentos->buscar($id);
        if ($orcamento === null || $orcamento->token() === null || ! hash_equals($orcamento->token(), $token)) {
            throw new TokenInvalido();
        }

        return $orcamento;
    }

    public function aprovar(int $id, string $token, string $correlationId): Orcamento
    {
        $orcamento = $this->orcamentos->buscar($id) ?? throw new TokenInvalido();
        $orcamento->aprovar($token);
        $this->orcamentos->salvar($orcamento);

        $this->eventos->publicar('billing.evt.orcamento-aprovado', $orcamento->sagaId, $correlationId, $orcamento->osId, [
            'orcamento_id' => $orcamento->id,
            'aprovado_em' => now()->toAtomString(),
        ]);

        return $orcamento;
    }

    public function recusar(int $id, string $token, string $correlationId): Orcamento
    {
        $orcamento = $this->orcamentos->buscar($id) ?? throw new TokenInvalido();
        $orcamento->recusar($token);
        $this->orcamentos->salvar($orcamento);

        $this->eventos->publicar('billing.evt.orcamento-recusado', $orcamento->sagaId, $correlationId, $orcamento->osId, [
            'orcamento_id' => $orcamento->id,
            'recusado_em' => now()->toAtomString(),
        ]);

        return $orcamento;
    }
}
