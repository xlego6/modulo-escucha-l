<table class="table table-hover table-striped">
    <thead>
        <tr>
            <th>Nombre Completo</th>
            <th style="width: 150px">Documento</th>
            <th style="width: 100px">Sexo</th>
            <th style="width: 150px">Grupo Etnico</th>
            <th style="width: 100px">Contacto</th>
            <th style="width: 80px">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($resultados as $persona)
        <tr>
            <td>
                <a href="{{ route('personas.show', $persona->id_persona) }}">
                    <strong>{{ $persona->fmt_nombre_completo }}</strong>
                </a>
                @if($persona->alias)
                <br><small class="text-muted">Alias: {{ $persona->alias }}</small>
                @endif
            </td>
            <td>
                @if($persona->num_documento)
                    <small class="text-muted">{{ $persona->rel_tipo_documento->descripcion ?? 'DOC' }}:</small><br>
                    {{ $persona->num_documento }}
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>{{ $persona->fmt_sexo }}</td>
            <td>
                @if($persona->rel_etnia)
                    {{ $persona->rel_etnia->descripcion }}
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($persona->telefono)
                    <i class="fas fa-phone text-muted"></i> {{ $persona->telefono }}
                @elseif($persona->correo_electronico)
                    <i class="fas fa-envelope text-muted"></i>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                <a href="{{ route('personas.show', $persona->id_persona) }}" class="btn btn-info btn-sm" title="Ver">
                    <i class="fas fa-eye"></i>
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center text-muted py-4">
                No se encontraron personas
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
