<?php

namespace Tests\Feature;

use App\Models\Orcamento;
use Domain\Cobranca\PagamentoRepository;
use Domain\Shared\Messaging\Envelope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Infrastructure\Messaging\Decisao;
use Infrastructure\Messaging\ProcessadorDeMensagens;
use Tests\TestCase;

class CobrancaTest extends TestCase
{
    use RefreshDatabase;

    private const SAGA = '5d2a3c4e-1f2b-4c3d-9e8f-0a1b2c3d4e5f';

    private int $orcamentoId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://billing.oficina']);
        $this->orcamentoId = Orcamento::create([
            'saga_id' => self::SAGA, 'os_id' => 42, 'cliente' => [], 'itens' => [],
            'valor_total' => 19980, 'status' => 'aprovado',
        ])->id;
    }

    private function cobrar(): Decisao
    {
        $envelope = Envelope::criar('billing.cmd.cobrar', self::SAGA, 'corr-saga', 42, [
            'orcamento_id' => $this->orcamentoId,
            'valor' => 19980,
            'pagador' => ['nome' => 'Ana', 'email' => 'ana@x.com', 'documento' => '52998224725'],
        ]);

        return app(ProcessadorDeMensagens::class)->processar($envelope->paraJson(), 0);
    }

    private function ultimoEvento(): Envelope
    {
        return Envelope::deJson(DB::table('outbox')->orderByDesc('id')->value('corpo'));
    }

    public function test_cobrar_cria_o_pagamento_e_devolve_o_link(): void
    {
        $this->assertSame(Decisao::Confirmar, $this->cobrar());

        $pagamento = app(PagamentoRepository::class)->buscarPorSaga(self::SAGA);
        $evento = $this->ultimoEvento();
        $this->assertSame('billing.evt.cobranca-criada', $evento->type);
        $this->assertSame([
            'pagamento_id' => $pagamento->id,
            'link_pagamento' => "https://billing.oficina/api/pagamentos/{$pagamento->id}/simular",
        ], $evento->payload);
        $this->assertSame("fake-{$pagamento->id}", $pagamento->gatewayId());
        $this->assertSame(19980, $pagamento->valor);
    }

    public function test_cobrar_de_novo_reaproveita_o_pagamento_da_saga(): void
    {
        $this->cobrar();
        $this->cobrar();

        $this->assertDatabaseCount('pagamentos', 1);
        $this->assertDatabaseCount('outbox', 2);
    }
}
