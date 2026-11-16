<?php

namespace Domain\Cobranca;

class Orcamento
{
    /**
     * @param array{nome: ?string, email: ?string, documento: ?string} $cliente
     * @param list<array{descricao: ?string, quantidade: int, valor_unitario: int}> $itens
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $sagaId,
        public readonly int $osId,
        public readonly array $cliente,
        public readonly array $itens,
        public readonly int $valorTotal,
        private StatusOrcamento $status = StatusOrcamento::Pendente,
        private ?string $token = null,
    ) {}

    public static function criar(string $sagaId, int $osId, array $cliente, array $itens): self
    {
        if ($itens === []) {
            throw new OrcamentoInvalido('O orçamento precisa de ao menos um item.');
        }

        $total = 0;
        foreach ($itens as $item) {
            $quantidade = (int) ($item['quantidade'] ?? 0);
            $valor = (int) ($item['valor_unitario'] ?? 0);
            if ($quantidade < 1 || $valor < 1) {
                throw new OrcamentoInvalido('Item "'.($item['descricao'] ?? '?').'" sem quantidade ou valor.');
            }
            $total += $quantidade * $valor;
        }

        return new self(null, $sagaId, $osId, $cliente, $itens, $total, StatusOrcamento::Pendente, bin2hex(random_bytes(20)));
    }

    public function status(): StatusOrcamento
    {
        return $this->status;
    }

    public function token(): ?string
    {
        return $this->token;
    }

    public function aprovar(string $token): void
    {
        $this->consumirToken($token);
        $this->status = StatusOrcamento::Aprovado;
    }

    public function recusar(string $token): void
    {
        $this->consumirToken($token);
        $this->status = StatusOrcamento::Recusado;
    }

    public function cancelar(): void
    {
        $this->status = StatusOrcamento::Cancelado;
        $this->token = null;
    }

    private function consumirToken(string $token): void
    {
        if ($this->status !== StatusOrcamento::Pendente || $this->token === null || ! hash_equals($this->token, $token)) {
            throw new TokenInvalido();
        }

        $this->token = null;
    }
}
