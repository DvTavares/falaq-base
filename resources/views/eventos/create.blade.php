@extends('layouts.app')

@section('title', 'Eventos — FalaQ')

@section('navbar_item')
    @auth
        <a href="{{ route('eventos.create') }}"> Criar Evento </a>
    @endauth
@endsection

@section('content')

<div>
    <!-- Because you are alive, everything is possible. - Thich Nhat Hanh -->

    <form action="{{ route('eventos.store') }}" method='POST'>
        @csrf
        <input name='titulo' />
        <input name='descricao' />
        <input name='data_evento' type="date" />
            <button type='submit'> Criar</button>
        </form>

</div>
@endsection
