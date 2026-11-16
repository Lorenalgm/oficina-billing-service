<?php

namespace Tests\Feature;

use App\Models\Orcamento;
use App\Models\Pagamento;
use Domain\Cobranca\GatewayPagamento;
use Domain\Shared\Messaging\Envelope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Infrastructure\Messaging\ProcessadorDeMensagens;
use Infrastructure\Pagamento\FakeGateway;
use Tests\TestCase;

class ResultadoEEstornoTest extends TestCase
{
    use RefreshDatabase;

    private const SAGA = '5d2a3c4e-1f2b-4c3d-9e8f-0a1b2c3d4e5f';

    private int $pagamentoId;

    protected function setUp(): void
    {
        parent::setUp();
        $orcamento = Orcamento::create(['saga_id' => self::SAGA, 'os_id' => 42, 'cliente' => [], 'itens' => [], 'valor_total' => 19980, 'status' => 'aprovado']);
        $this->pagamentoId = Pagamento::create([
            'saga_id' => self::SAGA, 'os_id' => 42, 'orcamento_id' => $orcamento->id,
            'valor' => 19980, 'status' => 'pendente', 'gateway_id' => 'fake-1', 'link' => 'https://x',
        ])->id;
    }

    private function eventos(): array
    {
        return DB::table('outbox')->orderBy('id')->pluck('corpo')->map(fn ($c) => Envelope::deJson($c))->all();
    }

    private function ultimoEvento(): Envelope
    {
        $eventos = $this->eventos();

        return end($eventos);
    }

    private function simular(string $resultado)
    {
        return $this->postJson("/api/pagamentos/{$this->pagamentoId}/simular", ['resultado' => $resultado], ['X-Request-Id' => 'corr-pagamento']);
    }

    private function estornar(): void
    {
        $envelope = Envelope::criar('billing.cmd.estornar', self::SAGA, 'corr-saga', 42, ['pagamento_id' => $this->pagamentoId, 'motivo' => 'fila cheia']);
        app(ProcessadorDeMensagens::class)->processar($envelope->paraJson(), 0);
    }

    public function test_pagamento_aprovado_confirma_para_a_saga_uma_vez(): void
    {
        $this->simular('aprovado')->assertOk()->assertJsonPath('data.status', 'aprovado');
        $this->simular('aprovado')->assertOk();

        [$evento] = $this->eventos();
        $this->assertCount(1, $this->eventos());
        $this->assertSame('billing.evt.pagamento-confirmado', $evento->type);
        $this->assertSame(self::SAGA, $evento->sagaId);
        $this->assertSame('corr-pagamento', $evento->correlationId);
        $this->assertSame(19980, $evento->payload['valor']);
        $this->assertSame('fake-1', $evento->payload['gateway_id']);
    }

    public function test_pagamento_recusado_avisa_a_saga(): void
    {
        $this->simular('recusado')->assertOk()->assertJsonPath('data.status', 'recusado');

        $this->assertSame('billing.evt.pagamento-recusado', $this->eventos()[0]->type);
    }

    public function test_simulacao_valida_entrada_e_so_existe_no_gateway_fake(): void
    {
        $this->simular('talvez')->assertStatus(422);
        $this->postJson('/api/pagamentos/999/simular', ['resultado' => 'aprovado'])->assertNotFound();

        config(['services.pagamento.gateway' => 'mercadopago']);
        $this->simular('aprovado')->assertNotFound();
    }

    public function test_estorno_de_pagamento_aprovado(): void
    {
        $this->simular('aprovado');

        $this->estornar();

        $this->assertSame('billing.evt.pagamento-estornado', $this->ultimoEvento()->type);
        $this->assertDatabaseHas('pagamentos', ['id' => $this->pagamentoId, 'status' => 'estornado']);
    }

    public function test_estorno_repetido_ou_sem_pagamento_aprovado_so_confirma(): void
    {
        $this->estornar();

        $this->assertSame('billing.evt.pagamento-estornado', $this->eventos()[0]->type);
        $this->assertDatabaseHas('pagamentos', ['id' => $this->pagamentoId, 'status' => 'pendente']);
    }

    public function test_falha_no_gateway_responde_estorno_falhou(): void
    {
        $this->app->instance(GatewayPagamento::class, new FakeGateway('https://x', falharEstorno: true));
        $this->simular('aprovado');

        $this->estornar();

        $evento = $this->ultimoEvento();
        $this->assertSame('billing.evt.estorno-falhou', $evento->type);
        $this->assertSame('Estorno indisponível no gateway fake.', $evento->payload['motivo']);
        $this->assertDatabaseHas('pagamentos', ['id' => $this->pagamentoId, 'status' => 'aprovado']);
    }
}
