@extends('layouts.app')

@section('title', 'Fila de Moderação — ' . $evento->titulo)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold m-0">🛡️ Fila de Moderação: {{ $evento->titulo }}</h4>
    <a href="{{ route('eventos.show', $evento->id) }}" class="btn btn-outline-secondary btn-sm">&larr; Voltar ao evento</a>
</div>

@forelse($moderacoes as $moderacao)
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <p class="fs-5 mb-1 text-white">{{ $moderacao->pergunta->texto }}</p>
            <p class="mb-2 text-secondary"><strong>Motivo:</strong> {{ $moderacao->motivo }}</p>
            <div class="d-flex justify-content-between align-items-center small">
                <span>
                    Status:
                    <span class="badge {{ $moderacao->status === 'aprovada' ? 'bg-success' : ($moderacao->status === 'rejeitada' ? 'bg-danger' : 'bg-warning text-dark') }}">
                        {{ $moderacao->status }}
                    </span>
                    <span class="text-secondary ms-2">{{ $moderacao->created_at->format('d/m/Y H:i') }}</span>
                </span>
                <div class="d-flex gap-2">
                    @foreach(['aprovada' => 'btn-success', 'rejeitada' => 'btn-danger'] as $status => $classe)
                        <form action="{{ route('moderacoes.status', $moderacao) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status }}">
                            <button type="submit" class="btn btn-sm {{ $classe }}" @disabled($moderacao->status === $status)>
                                {{ $status === 'aprovada' ? 'Aprovar' : 'Rejeitar' }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="alert alert-dark text-center p-4">
        Nenhuma denúncia para este evento. 🎉
    </div>
@endforelse

<div class="d-flex justify-content-center mt-4">
    {{ $moderacoes->links('pagination::bootstrap-5') }}
</div>
@endsection
