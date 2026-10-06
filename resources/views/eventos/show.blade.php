@extends('layouts.app')

@section('title', $evento->titulo . ' — FalaQ')

@section('content')
<div class="row">
    <!-- Formularço de envio de Pergunta -->
    <div class="col-md-5 mb-4">
        @if(session('permissao'))
            <div class="text-red-500 fw-bold">
                {{ session('permissao') }}
            </div>
        @endif
        @can('delete', $evento)
            <form action="{{ route('eventos.destroy', $evento)  }}" method="post">
                @csrf
                @method('DELETE')
                <button type="submit">Deletar Evento</button>
            </form>
        @endcan

        <div class="card shadow-sm p-3">
            <h4 class="fw-bold mb-3">💬 Faça sua Pergunta</h4>
            <form action="{{ route('eventos.perguntas.store', $evento->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="texto" class="form-label text-secondary">Texto da Pergunta</label>

                    <textarea name="texto" id="texto" rows="4" 
                              class="form-control bg-dark text-white border-secondary @error('texto') is-invalid @enderror"
                              placeholder="Digite sua dúvida ou comentário para o palestrante..."></textarea>

                    @error('texto')
                        <div class="invalid-feedback fw-bold">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Enviar Pergunta</button>
            </form>
        </div>
    </div>

    <!-- Lista de Perguntas (TICKET #002) -->
    <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold m-0">📋 Perguntas do Evento</h4>
            <span class="text-secondary small">Total no Banco: {{ $evento->perguntas->count() }}</span>
        </div>

        @forelse($perguntas as $pergunta)
            <div class="card mb-3 shadow-sm border-start border-4 border-primary">
                <div class="card-body">
                    <p class="fs-5 mb-2 text-white">{{ $pergunta->texto }}</p>
                    <div class="d-flex justify-content-between align-items-center text-secondary small">
                        <span>Status: <span class="badge bg-success">{{ $pergunta->status }}</span></span>
                        <span>{{ $pergunta->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="d-flex justify-content-end align-items-center gap-2 mt-3">
                        <span class="fw-bold text-white">{{ $pergunta->votos_count }} {{ $pergunta->votos_count === 1 ? 'voto' : 'votos' }}</span>
                        @auth
                            <form action="{{ route('perguntas.votar', $pergunta) }}" method="POST">
                                @csrf
                                @if($pergunta->votada_pelo_usuario)
                                    <button type="submit" class="btn btn-sm btn-primary fw-bold" title="Clique para remover seu voto">▲ Votado</button>
                                @else
                                    <button type="submit" class="btn btn-sm btn-outline-primary fw-bold" title="Votar nesta pergunta">▲ Votar</button>
                                @endif
                            </form>
                        @endauth
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-dark text-center p-4">
                Nenhuma pergunta enviada ainda. Seja o primeiro!
            </div>
        @endforelse

        <!-- TICKET #002: Renderização dos Botões de Paginação -->
        @if(method_exists($perguntas, 'links'))
            <div class="d-flex justify-content-center mt-4">
                
            </div>
        @endif
    </div>
</div>
@endsection
