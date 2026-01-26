<table class="table table-hover table-striped">
    <thead>
        <tr>
            <th style="width: 120px">Codigo</th>
            <th>Titulo</th>
            <th style="width: 100px">Fecha</th>
            <th style="width: 150px">Lugar</th>
            <th style="width: 150px">Entrevistador</th>
            <th style="width: 80px">Adjuntos</th>
            <th style="width: 80px">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($resultados as $entrevista)
        <tr>
            <td>
                <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}">
                    <strong>{{ $entrevista->entrevista_codigo }}</strong>
                </a>
            </td>
            <td>
                {{ \Illuminate\Support\Str::limit($entrevista->titulo, 50) }}
                @if($entrevista->anotaciones)
                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($entrevista->anotaciones, 60) }}</small>
                @endif
            </td>
            <td>{{ $entrevista->fmt_fecha }}</td>
            <td>
                @if($entrevista->rel_lugar_entrevista)
                    {{ $entrevista->rel_lugar_entrevista->descripcion }}
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($entrevista->rel_entrevistador && $entrevista->rel_entrevistador->rel_usuario)
                    {{ $entrevista->rel_entrevistador->rel_usuario->name }}
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td class="text-center">
                @php $num_adjuntos = $entrevista->rel_adjuntos ? $entrevista->rel_adjuntos->count() : 0; @endphp
                @if($num_adjuntos > 0)
                    <span class="badge badge-info">{{ $num_adjuntos }}</span>
                @else
                    <span class="text-muted">0</span>
                @endif
            </td>
            <td>
                <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}" class="btn btn-info btn-sm" title="Ver">
                    <i class="fas fa-eye"></i>
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-muted py-4">
                No se encontraron entrevistas
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
