<?php

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();

    $this->usuario = User::factory()->create();
    $this->evento = Evento::create(['titulo' => 'Evento de Teste']);
    $this->pergunta = Pergunta::create([
        'evento_id' => $this->evento->id,
        'texto' => 'Pergunta de teste',
    ]);
});

it('impede voto duplicado do mesmo usuario na mesma pergunta', function () {
    DB::table('pergunta_user')->insert(['pergunta_id' => $this->pergunta->id, 'user_id' => $this->usuario->id]);
    DB::table('pergunta_user')->insert(['pergunta_id' => $this->pergunta->id, 'user_id' => $this->usuario->id]);
})->throws(QueryException::class);

it('define os relacionamentos N:M nos models', function () {
    expect($this->pergunta->votos())->toBeInstanceOf(BelongsToMany::class)
        ->and($this->usuario->perguntasVotadas())->toBeInstanceOf(BelongsToMany::class)
        ->and($this->pergunta->votos()->getTable())->toBe('pergunta_user')
        ->and($this->usuario->perguntasVotadas()->getTable())->toBe('pergunta_user');
});

it('impede visitante anonimo de votar', function () {
    $this->post(route('perguntas.votar', $this->pergunta))
        ->assertRedirect();

    $this->assertDatabaseCount('pergunta_user', 0);
});

it('registra o voto quando o usuario ainda nao votou', function () {
    $this->actingAs($this->usuario)
        ->from(route('eventos.show', $this->evento->id))
        ->post(route('perguntas.votar', $this->pergunta))
        ->assertRedirect(route('eventos.show', $this->evento->id))
        ->assertSessionHas('sucesso');

    $this->assertDatabaseHas('pergunta_user', [
        'pergunta_id' => $this->pergunta->id,
        'user_id' => $this->usuario->id,
    ]);
});

it('remove o voto quando o usuario ja tinha votado', function () {
    $this->pergunta->votos()->attach($this->usuario->id);

    $this->actingAs($this->usuario)
        ->post(route('perguntas.votar', $this->pergunta))
        ->assertRedirect();

    $this->assertDatabaseMissing('pergunta_user', [
        'pergunta_id' => $this->pergunta->id,
        'user_id' => $this->usuario->id,
    ]);
});

it('ordena as perguntas do evento pelo numero de votos', function () {
    $popular = Pergunta::create(['evento_id' => $this->evento->id, 'texto' => 'Pergunta mais votada']);
    $media = Pergunta::create(['evento_id' => $this->evento->id, 'texto' => 'Pergunta com um voto']);

    $popular->votos()->attach(User::factory()->count(3)->create());
    $media->votos()->attach(User::factory()->create());

    $this->get(route('eventos.show', $this->evento->id))
        ->assertOk()
        ->assertSeeInOrder(['Pergunta mais votada', 'Pergunta com um voto', 'Pergunta de teste']);
});

it('destaca o botao quando o usuario ja votou', function () {
    $this->actingAs($this->usuario)
        ->get(route('eventos.show', $this->evento->id))
        ->assertSee('▲ Votar')
        ->assertDontSee('▲ Votado');

    $this->pergunta->votos()->attach($this->usuario->id);

    $this->actingAs($this->usuario)
        ->get(route('eventos.show', $this->evento->id))
        ->assertSee('▲ Votado');
});
