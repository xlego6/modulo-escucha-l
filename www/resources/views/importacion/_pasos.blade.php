{{-- Partial: barra de progreso de los 4 pasos --}}
@php
    $pasos = [
        1 => 'Subir CSV',
        2 => 'Mapear catálogos',
        3 => 'Confirmar',
        4 => 'Procesar',
    ];
@endphp
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row text-center">
            @foreach($pasos as $num => $etiqueta)
            @php
                $clase = $num < $paso_actual ? 'text-success' : ($num == $paso_actual ? 'text-primary font-weight-bold' : 'text-muted');
                $icono = $num < $paso_actual ? 'fas fa-check-circle' : ($num == $paso_actual ? 'fas fa-dot-circle' : 'far fa-circle');
            @endphp
            <div class="col {{ $clase }}">
                <i class="{{ $icono }}"></i> {{ $num }}. {{ $etiqueta }}
            </div>
            @if(!$loop->last)
            <div class="col-auto text-muted pt-1"><i class="fas fa-chevron-right"></i></div>
            @endif
            @endforeach
        </div>
    </div>
</div>
