<?php

namespace Domain\Cobranca;

class Pagamento
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $sagaId,
        public readonly int $osId,
        public readonly int $orcamentoId,
        public readonly int $valor,
        private StatusPagamento $status = StatusPagamento::Pendente,
        private ?string $gatewayId = null,
        private ?string $link = null,
    ) {}

    public static function criar(string $sagaId, int $osId, int $orcamentoId, int $valor): self
    {
        if ($valor < 1) {
            throw new PagamentoInvalido('O valor da cobrança deve ser positivo.');
        }

        return new self(null, $sagaId, $osId, $orcamentoId, $valor);
    }

    public function status(): StatusPagamento { return $this->status; }
    public function gatewayId(): ?string { return $this->gatewayId; }
    public function link(): ?string { return $this->link; }

    public function registrarCobranca(string $gatewayId, string $link): void
    {
        $this->gatewayId = $gatewayId;
        $this->link = $link;
    }

    public function aprovar(): void
    {
        $this->mudar(StatusPagamento::Pendente, StatusPagamento::Aprovado);
    }

    public function recusar(): void
    {
        $this->mudar(StatusPagamento::Pendente, StatusPagamento::Recusado);
    }

    public function estornar(): void
    {
        $this->mudar(StatusPagamento::Aprovado, StatusPagamento::Estornado);
    }

    private function mudar(StatusPagamento $de, StatusPagamento $para): void
    {
        if ($this->status !== $de) {
            throw new PagamentoInvalido("Pagamento {$this->status->value} não pode ir para {$para->value}.");
        }

        $this->status = $para;
    }
}
