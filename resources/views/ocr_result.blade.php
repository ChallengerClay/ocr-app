@extends('layouts.app')

@section('title', 'Resultados')

@section('content')
    <div class="bg-default my-2">    
        @foreach($text as $key => $detail)
            @if(empty($detail))
                @continue
            @else
                <div class="row my-3">
                    <h2 class="text-center">{{ucfirst($key)}}</h2>
                </div>
                @if(is_array($detail) && $key == 'nutricional')
                <div class="container my-3">
                               <table class="table nutrition-table align-middle shadow-sm">
                                <thead class="table-dark">
                                    <tr class="text-center">
                                        <th>Nutriente</th>
                                        <th>Valor</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                    @foreach($detail as $sk=> $detalle)
                        @if(is_array($detalle))
                            <tr class="text-center">
                                <td class="text-center">{{ __($sk)  }}</td>
                                <td class="text-center texto-copia">{{ $detalle['valor'] !== '' ? $detalle['valor'] : '—' }} {{ $detalle['valor'] !== '' ? $detalle['unidad'] : '' }}</td>
                                <td class="text-center">
                                    @if($detalle['valor'] !== '')
                                        <button class="btn btn-sm btn-outline-primary copiar-btn">
                                            <i class="bi bi-clipboard"></i> Copiar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                        </tbody>
                    </table>
                </div>
                @elseif(is_array($detail) && $key != 'nutricional')
                <div class="d-flex justify-content-center ">
                    @foreach($detail as $sk =>$detalle)
                        <div class="card ml-3 border border-secondary">
                            @if(strtolower($key) != 'advertencias')
                                <div class="card-title text-center bg-secondary font-weight-bold">
                                    <p class="mb-0"><strong>{{ucfirst($sk)}}</strong></p>
                                </div>
                            @endif
                            <div class="card-body">
                                {{$detalle}}
                            </div>
                        </div>
                    @endforeach
                </div>
                @else
                <div class="px-3">
                    <div class="card">
                        {{$detail}}
                    </div>
                </div>
                @endif
            @endif
        @endforeach
    </div>
@endsection
@push('js')
<script>
document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.copiar-btn').forEach(boton => {
        boton.addEventListener('click', () => {

            // fila del botón clickeado
            const fila = boton.closest('tr');

            // SOLO la columna valor
            const texto = fila.querySelector('.texto-copia').innerText.trim();

            // copiar al portapapeles
            navigator.clipboard.writeText(texto)
                .then(() => {
                    // opcional: mensaje de éxito
                    alert('Valor copiado: ' + texto);
                })
                .catch(err => {
                    console.error('Error al copiar', err);
                });

        });
    });

});
</script>
@endpush
