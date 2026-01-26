@if($resultados->count() > 0)
<table class="table table-hover">
    <thead>
        <tr>
            <th style="width: 50px"></th>
            <th>Documento</th>
            <th>Entrevista</th>
            <th>Tipo</th>
            <th style="width: 120px">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($resultados as $adjunto)
        <tr class="{{ $adjunto->coincidencia_texto ? 'resultado-adjunto' : '' }}">
            <td class="text-center">
                @if($adjunto->es_audio)
                    <i class="fas fa-volume-up fa-2x text-info"></i>
                @elseif($adjunto->es_video)
                    <i class="fas fa-video fa-2x text-danger"></i>
                @elseif($adjunto->es_documento)
                    <i class="fas fa-file-pdf fa-2x text-danger"></i>
                @else
                    <i class="fas fa-file fa-2x text-secondary"></i>
                @endif
            </td>
            <td>
                <strong>{{ $adjunto->nombre_original }}</strong>
                <br>
                <small class="text-muted">
                    {{ $adjunto->fmt_tamano }}
                    @if($adjunto->duracion)
                        | {{ $adjunto->fmt_duracion }}
                    @endif
                </small>
                @if($adjunto->coincidencia_texto && $adjunto->extracto)
                <div class="extracto-texto mt-2">
                    <i class="fas fa-quote-left text-muted"></i>
                    {!! $adjunto->extracto !!}
                </div>
                @endif
            </td>
            <td>
                @if($adjunto->rel_entrevista)
                <a href="{{ route('entrevistas.show', $adjunto->rel_entrevista->id_e_ind_fvt) }}">
                    <span class="badge badge-primary">{{ $adjunto->rel_entrevista->entrevista_codigo }}</span>
                </a>
                <br>
                <small>{{ \Illuminate\Support\Str::limit($adjunto->rel_entrevista->titulo, 40) }}</small>
                @else
                <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($adjunto->rel_tipo)
                    <span class="badge badge-secondary">{{ $adjunto->rel_tipo->descripcion }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                <div class="btn-group">
                    <a href="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}" class="btn btn-info btn-sm" title="Ver" target="_blank">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('adjuntos.descargar', $adjunto->id_adjunto) }}" class="btn btn-secondary btn-sm" title="Descargar">
                        <i class="fas fa-download"></i>
                    </a>
                    @if($adjunto->rel_entrevista)
                    <a href="{{ route('adjuntos.gestionar', $adjunto->rel_entrevista->id_e_ind_fvt) }}" class="btn btn-warning btn-sm" title="Gestionar adjuntos">
                        <i class="fas fa-folder-open"></i>
                    </a>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="text-center py-4">
    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
    <p class="text-muted">No se encontraron documentos</p>
</div>
@endif
