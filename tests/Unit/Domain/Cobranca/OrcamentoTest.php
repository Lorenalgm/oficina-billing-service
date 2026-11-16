<?php

namespace Tests\Unit\Domain\Cobranca;

use Domain\Cobranca\Orcamento;
use Domain\Cobranca\OrcamentoInvalido;
use Domain\Cobranca\StatusOrcamento;
use Domain\Cobranca\TokenInvalido;
use PHPUnit\Framework\TestCase;

class OrcamentoTest extends TestCase
{
    private function novo(): Orcamento
    {
        return Orcamento::criar('saga-1', 42, ['nome' => 'Ana', 'email' => 'ana@x.com', 'documento' => '52998224725'], [
            ['descricao' => 'Troca de óleo', 'quantidade' => 1, 'valor_unitario' => 10000],
            ['descricao' => 'Óleo 5W30', 'quantidade' => 2, 'valor_unitario' => 4990],
        ]);
    }

    public function test_criar_calcula_o_total_e_gera_token(): void
    {
        $orcamento = $this->novo();

        $this->assertSame(19980, $orcamento->valorTotal);
        $this->assertSame(StatusOrcamento::Pendente, $orcamento->status());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $orcamento->token());
        $this->assertNull($orcamento->id);
    }

    public function test_sem_itens_e_invalido(): void
    {
        $this->expectException(OrcamentoInvalido::class);

        Orcamento::criar('saga-1', 42, [], []);
    }

    public function test_item_sem_valor_e_invalido(): void
    {
        $this->expectException(OrcamentoInvalido::class);
        $this->expectExceptionMessage('Filtro');

        Orcamento::criar('saga-1', 42, [], [['descricao' => 'Filtro', 'quantidade' => 1, 'valor_unitario' => 0]]);
    }

    public function test_aprovar_com_token_consome_o_token(): void
    {
        $orcamento = $this->novo();

        $orcamento->aprovar($orcamento->token());

        $this->assertSame(StatusOrcamento::Aprovado, $orcamento->status());
        $this->assertNull($orcamento->token());
    }

    public function test_recusar_com_token(): void
    {
        $orcamento = $this->novo();

        $orcamento->recusar($orcamento->token());

        $this->assertSame(StatusOrcamento::Recusado, $orcamento->status());
    }

    public function test_token_errado_e_recusado(): void
    {
        $orcamento = $this->novo();

        $this->expectException(TokenInvalido::class);

        $orcamento->aprovar('errado');
    }

    public function test_token_nao_pode_ser_reusado(): void
    {
        $orcamento = $this->novo();
        $token = $orcamento->token();
        $orcamento->aprovar($token);

        $this->expectException(TokenInvalido::class);

        $orcamento->recusar($token);
    }

    public function test_cancelar_invalida_o_token(): void
    {
        $orcamento = $this->novo();

        $orcamento->cancelar();

        $this->assertSame(StatusOrcamento::Cancelado, $orcamento->status());
        $this->assertNull($orcamento->token());
    }
}
