<?php

namespace Tests\Feature;

use Domain\Cobranca\Orcamento;
use Domain\Cobranca\OrcamentoRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentOrcamentoRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_salva_e_busca_por_id_e_por_saga(): void
    {
        $repositorio = app(OrcamentoRepository::class);
        $novo = Orcamento::criar('5d2a3c4e-1f2b-4c3d-9e8f-0a1b2c3d4e5f', 42, ['nome' => 'Ana'], [
            ['descricao' => 'Troca de óleo', 'quantidade' => 1, 'valor_unitario' => 10000],
        ]);

        $salvo = $repositorio->salvar($novo);
        $salvo->aprovar($novo->token());
        $repositorio->salvar($salvo);

        $this->assertNotNull($salvo->id);
        $this->assertEquals($salvo, $repositorio->buscar($salvo->id));
        $this->assertEquals($salvo, $repositorio->buscarPorSaga($novo->sagaId));
        $this->assertNull($repositorio->buscar(999));
        $this->assertNull($repositorio->buscarPorSaga('outra'));
        $this->assertDatabaseCount('orcamentos', 1);
    }
}
