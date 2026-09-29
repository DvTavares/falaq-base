<?php

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();

    $this->donoEvento = User::factory()->create();
    $this->autor = User::factory()->create();
    $this->intruso = User::factory()->create();

    $this->evento = Evento::create([
        'titulo' => 'Evento de Teste',
        'user_id' => $this->donoEvento->id,
    ]);

    $this->pergunta = Pergunta::create([
        'evento_id' => $this->evento->id,
        'user_id' => $this->autor->id,
        'texto' => 'Pergunta do autor',
    ]);
});

it('permite que o autor exclua a propria pergunta', function () {
    $this->actingAs($this->autor)
        ->delete(route('perguntas.destroy', $this->pergunta))
        ->assertRedirect(route('eventos.show', $this->evento->id));

    $this->assertDatabaseMissing('perguntas', ['id' => $this->pergunta->id]);
});

it('permite que o dono do evento exclua pergunta de outro usuario', function () {
    $this->actingAs($this->donoEvento)
        ->delete(route('perguntas.destroy', $this->pergunta))
        ->assertRedirect(route('eventos.show', $this->evento->id));

    $this->assertDatabaseMissing('perguntas', ['id' => $this->pergunta->id]);
});

it('retorna 403 quando outro usuario forca a exclusao', function () {
    $this->actingAs($this->intruso)
        ->delete(route('perguntas.destroy', $this->pergunta))
        ->assertForbidden();

    $this->assertDatabaseHas('perguntas', ['id' => $this->pergunta->id]);
});

it('redireciona visitante anonimo para o login', function () {
    $this->delete(route('perguntas.destroy', $this->pergunta))
        ->assertRedirect();

    $this->assertDatabaseHas('perguntas', ['id' => $this->pergunta->id]);
});

it('exibe o botao de excluir apenas para quem tem permissao', function () {
    $acao = route('perguntas.destroy', $this->pergunta);

    $this->actingAs($this->autor)
        ->get(route('eventos.show', $this->evento->id))
        ->assertSee($acao, false);

    $this->actingAs($this->donoEvento)
        ->get(route('eventos.show', $this->evento->id))
        ->assertSee($acao, false);

    $this->actingAs($this->intruso)
        ->get(route('eventos.show', $this->evento->id))
        ->assertDontSee($acao, false);
});
