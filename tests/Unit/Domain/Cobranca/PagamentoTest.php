<?php

namespace Tests\Unit\Domain\Cobranca;

use Domain\Cobranca\Pagamento;
use Domain\Cobranca\PagamentoInvalido;
use Domain\Cobranca\StatusPagamento;
use PHPUnit\Framework\TestCase;

class PagamentoTest extends TestCase
{
    public function test_nasce_pendente_e_registra_a_cobranca(): void
    {
        $pagamento = Pagamento::criar('saga', 42, 7, 19980);
        $pagamento->registrarCobranca('mp-1', 'https://mp/1');

        $this->assertSame(StatusPagamento::Pendente, $pagamento->status());
        $this->assertSame('mp-1', $pagamento->gatewayId());
        $this->assertSame('https://mp/1', $pagamento->link());
    }

    public function test_valor_precisa_ser_positivo(): void
    {
        $this->expectException(PagamentoInvalido::class);

        Pagamento::criar('saga', 42, 7, 0);
    }

    public function test_aprovado_pode_ser_estornado(): void
    {
        $pagamento = Pagamento::criar('saga', 42, 7, 100);
        $pagamento->aprovar();
        $pagamento->estornar();

        $this->assertSame(StatusPagamento::Estornado, $pagamento->status());
    }

    public function test_recusado_nao_pode_ser_aprovado(): void
    {
        $pagamento = Pagamento::criar('saga', 42, 7, 100);
        $pagamento->recusar();

        $this->expectException(PagamentoInvalido::class);

        $pagamento->aprovar();
    }

    public function test_pendente_nao_pode_ser_estornado(): void
    {
        $this->expectException(PagamentoInvalido::class);

        Pagamento::criar('saga', 42, 7, 100)->estornar();
    }
}
