<?php

namespace App\Http\Controllers;

use App\Models\Pergunta;

class PerguntaController extends Controller
{
    public function votar(Pergunta $pergunta)
    {
        $resultado = $pergunta->votos()->toggle(auth()->id());

        $mensagem = count($resultado['attached']) > 0
            ? 'Voto registrado!'
            : 'Voto removido.';

        return back()->with('sucesso', $mensagem);
    }
}
