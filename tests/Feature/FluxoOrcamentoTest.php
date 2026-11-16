<?php

namespace Tests\Feature;

use Domain\Shared\Messaging\Envelope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Infrastructure\Messaging\Decisao;
use Infrastructure\Messaging\ProcessadorDeMensagens;
use Tests\TestCase;

class FluxoOrcamentoTest extends TestCase
{
    use RefreshDatabase;

    private const SAGA = '5d2a3c4e-1f2b-4c3d-9e8f-0a1b2c3d4e5f';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://billing.oficina']);
    }

    private function comando(string $type, array $payload): void
    {
        $envelope = Envelope::criar($type, self::SAGA, 'corr-saga', 42, $payload);

        $this->assertSame(Decisao::Confirmar, app(ProcessadorDeMensagens::class)->processar($envelope->paraJson(), 0));
    }

    private function gerar(array $itens = [['descricao' => 'Troca de óleo', 'quantidade' => 1, 'valor_unitario' => 10000], ['descricao' => 'Óleo', 'quantidade' => 2, 'valor_unitario' => 4990]]): void
    {
        $this->comando('billing.cmd.gerar-orcamento', [
            'cliente' => ['nome' => 'Ana', 'email' => 'ana@x.com', 'documento' => '52998224725'],
            'itens' => $itens,
            'valor_total' => 1,
        ]);
    }

    private function ultimoEvento(): Envelope
    {
        return Envelope::deJson(DB::table('outbox')->orderByDesc('id')->value('corpo'));
    }

    /** @return array{0: int, 1: string} */
    private function idEToken(): array
    {
        $link = $this->ultimoEvento()->payload['link_aprovacao'];
        preg_match('#/api/orcamentos/(\d+)\?token=([0-9a-f]+)$#', $link, $m);

        return [(int) $m[1], $m[2]];
    }

    public function test_gerar_orcamento_responde_com_total_e_link_de_aprovacao(): void
    {
        $this->gerar();

        $evento = $this->ultimoEvento();
        $this->assertSame('billing.evt.orcamento-gerado', $evento->type);
        $this->assertSame(self::SAGA, $evento->sagaId);
        $this->assertSame('corr-saga', $evento->correlationId);
        $this->assertSame(19980, $evento->payload['valor_total']);
        $this->assertStringStartsWith('https://billing.oficina/api/orcamentos/', $evento->payload['link_aprovacao']);
        $this->assertDatabaseHas('orcamentos', ['saga_id' => self::SAGA, 'status' => 'pendente', 'valor_total' => 19980]);
    }

    public function test_orcamento_invalido_responde_com_falha(): void
    {
        $this->gerar([['descricao' => 'Filtro', 'quantidade' => 1, 'valor_unitario' => 0]]);

        $evento = $this->ultimoEvento();
        $this->assertSame('billing.evt.orcamento-falhou', $evento->type);
        $this->assertStringContainsString('Filtro', $evento->payload['motivo']);
        $this->assertDatabaseCount('orcamentos', 0);
    }

    public function test_cliente_consulta_e_aprova_pelo_link(): void
    {
        $this->gerar();
        [$id, $token] = $this->idEToken();

        $this->getJson("/api/orcamentos/{$id}?token={$token}")
            ->assertOk()
            ->assertJsonPath('data.valor_total', 19980)
            ->assertJsonPath('data.status', 'pendente');

        $this->postJson("/api/orcamentos/{$id}/aprovar", ['token' => $token], ['X-Request-Id' => 'corr-cliente'])
            ->assertOk()
            ->assertJsonPath('data.status', 'aprovado');

        $evento = $this->ultimoEvento();
        $this->assertSame('billing.evt.orcamento-aprovado', $evento->type);
        $this->assertSame(['orcamento_id', 'aprovado_em'], array_keys($evento->payload));
        $this->assertSame(self::SAGA, $evento->sagaId);
        $this->assertSame('corr-cliente', $evento->correlationId);
    }

    public function test_cliente_recusa_pelo_link(): void
    {
        $this->gerar();
        [$id, $token] = $this->idEToken();

        $this->postJson("/api/orcamentos/{$id}/recusar?token={$token}")->assertOk()->assertJsonPath('data.status', 'recusado');

        $this->assertSame('billing.evt.orcamento-recusado', $this->ultimoEvento()->type);
    }

    public function test_token_invalido_ou_reusado_e_recusado_sem_publicar(): void
    {
        $this->gerar();
        [$id, $token] = $this->idEToken();

        $this->postJson("/api/orcamentos/{$id}/aprovar", ['token' => 'errado'])->assertUnauthorized();
        $this->getJson("/api/orcamentos/{$id}")->assertUnauthorized();
        $this->postJson('/api/orcamentos/999/aprovar', ['token' => $token])->assertUnauthorized();

        $this->postJson("/api/orcamentos/{$id}/aprovar", ['token' => $token])->assertOk();
        $this->postJson("/api/orcamentos/{$id}/recusar", ['token' => $token])->assertUnauthorized();

        $this->assertDatabaseCount('outbox', 2);
    }

    public function test_cancelar_orcamento_confirma_para_a_saga(): void
    {
        $this->gerar();

        $this->comando('billing.cmd.cancelar-orcamento', ['motivo' => 'pagamento recusado']);

        $this->assertSame('billing.evt.orcamento-cancelado', $this->ultimoEvento()->type);
        $this->assertDatabaseHas('orcamentos', ['saga_id' => self::SAGA, 'status' => 'cancelado', 'token' => null]);
    }

    public function test_cancelar_sem_orcamento_tambem_confirma(): void
    {
        $this->comando('billing.cmd.cancelar-orcamento', ['motivo' => 'teste']);

        $this->assertSame(['orcamento_id' => null], $this->ultimoEvento()->payload);
    }
}
