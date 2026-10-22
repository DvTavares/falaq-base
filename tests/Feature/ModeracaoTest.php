<?php

use App\Models\Evento;
use App\Models\Moderacao;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();

    $this->dono = User::factory()->create();
    $this->participante = User::factory()->create();
    $this->evento = Evento::create(['titulo' => 'Evento de Teste', 'user_id' => $this->dono->id]);
    $this->pergunta = Pergunta::create([
        'evento_id' => $this->evento->id,
        'user_id' => $this->participante->id,
        'texto' => 'Pergunta ofensiva de teste',
    ]);
});

// Fase 1: Fundação de Dados

it('cria a tabela moderacoes com as colunas e o status padrao', function () {
    expect(Schema::hasColumns('moderacoes', ['id', 'pergunta_id', 'motivo', 'status', 'created_at', 'updated_at']))->toBeTrue();

    $moderacao = Moderacao::create(['pergunta_id' => $this->pergunta->id, 'motivo' => 'Conteúdo ofensivo']);

    expect($moderacao->fresh()->status)->toBe('analisando');
});

it('remove as moderacoes em cascata ao excluir a pergunta', function () {
    $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);

    $this->pergunta->delete();

    expect(Moderacao::count())->toBe(0);
});

it('define os relacionamentos 1:N entre Pergunta e Moderacao', function () {
    $moderacao = $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);

    expect($this->pergunta->moderacoes())->toBeInstanceOf(HasMany::class)
        ->and($moderacao->pergunta())->toBeInstanceOf(BelongsTo::class)
        ->and($moderacao->pergunta->is($this->pergunta))->toBeTrue()
        ->and((new Moderacao)->getFillable())->toBe(['pergunta_id', 'motivo', 'status']);
});

// Fase 2: Entrada Segura & Persistência

it('registra a denuncia associada a pergunta', function () {
    $this->actingAs($this->participante)
        ->post(route('moderacoes.store', $this->pergunta), ['motivo' => 'Pergunta fora do escopo da palestra'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('moderacoes', [
        'pergunta_id' => $this->pergunta->id,
        'motivo' => 'Pergunta fora do escopo da palestra',
        'status' => 'analisando',
    ]);
});

it('valida o motivo da denuncia', function (?string $motivo) {
    $this->actingAs($this->participante)
        ->post(route('moderacoes.store', $this->pergunta), ['motivo' => $motivo])
        ->assertSessionHasErrors('motivo');

    expect(Moderacao::count())->toBe(0);
})->with([
    'vazio' => [null],
    'curto demais' => ['curto'],
    'longo demais' => [str_repeat('a', 256)],
]);

it('impede visitante anonimo de denunciar', function () {
    $this->post(route('moderacoes.store', $this->pergunta), ['motivo' => 'Pergunta fora do escopo da palestra'])
        ->assertRedirect();

    expect(Moderacao::count())->toBe(0);
});

// Fase 3: Autorização & Performance

it('lista a fila de moderacao do evento para o dono com eager loading', function () {
    $outroEvento = Evento::create(['titulo' => 'Outro evento', 'user_id' => $this->dono->id]);
    $outraPergunta = Pergunta::create(['evento_id' => $outroEvento->id, 'texto' => 'Pergunta de outro evento']);
    $outraPergunta->moderacoes()->create(['motivo' => 'Denúncia de outro evento']);

    foreach (range(1, 3) as $i) {
        $this->pergunta->moderacoes()->create(['motivo' => "Denúncia número {$i}"]);
    }

    DB::enableQueryLog();
    $resposta = $this->actingAs($this->dono)->get(route('moderacoes.index', $this->evento));
    $carregamentosDePergunta = collect(DB::getQueryLog())
        ->filter(fn ($q) => str_starts_with($q['query'], 'select * from "perguntas"'))
        ->count();

    $resposta->assertOk()
        ->assertViewHas('moderacoes', fn ($moderacoes) => $moderacoes->total() === 3
            && $moderacoes->getCollection()->every(fn ($m) => $m->relationLoaded('pergunta')))
        ->assertSee('Denúncia número 1')
        ->assertDontSee('Denúncia de outro evento');

    expect($carregamentosDePergunta)->toBe(1);
});

it('bloqueia a fila de moderacao para quem nao e dono do evento', function () {
    $this->actingAs($this->participante)
        ->get(route('moderacoes.index', $this->evento))
        ->assertForbidden();
});

it('permite ao dono do evento aprovar ou rejeitar a moderacao', function (string $status) {
    $moderacao = $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);

    $this->actingAs($this->dono)
        ->patch(route('moderacoes.status', $moderacao), ['status' => $status])
        ->assertRedirect();

    expect($moderacao->fresh()->status)->toBe($status);
})->with(['aprovada', 'rejeitada']);

it('retorna 403 quando outro usuario tenta alterar o status', function () {
    $moderacao = $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);

    $this->actingAs($this->participante)
        ->patch(route('moderacoes.status', $moderacao), ['status' => 'aprovada'])
        ->assertForbidden();

    expect($moderacao->fresh()->status)->toBe('analisando');
});

it('rejeita status invalido na transicao', function () {
    $moderacao = $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);

    $this->actingAs($this->dono)
        ->patch(route('moderacoes.status', $moderacao), ['status' => 'qualquer'])
        ->assertSessionHasErrors('status');

    expect($moderacao->fresh()->status)->toBe('analisando');
});
