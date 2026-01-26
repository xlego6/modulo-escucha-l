@extends('layouts.app')

@section('title', 'Editar Entrevista')
@section('content_header', 'Editar Entrevista')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    Editando: {{ $entrevista->entrevista_codigo }}
                </h3>
            </div>
            <form action="{{ route('entrevistas.update', $entrevista->id_e_ind_fvt) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-warning mb-3"><i class="fas fa-info-circle"></i> Informacion Basica</h5>

                            <div class="callout callout-warning">
                                <small>El codigo y numero de entrevista no pueden modificarse</small>
                                <h5 class="mb-0">{{ $entrevista->entrevista_codigo }}</h5>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="entrevista_fecha">Fecha de Entrevista <span class="text-danger">*</span></label>
                                        <input type="date" name="entrevista_fecha" id="entrevista_fecha"
                                            class="form-control @error('entrevista_fecha') is-invalid @enderror"
                                            value="{{ old('entrevista_fecha', $entrevista->entrevista_fecha) }}" required>
                                        @error('entrevista_fecha')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tiempo_entrevista">Duracion (minutos)</label>
                                        <input type="number" name="tiempo_entrevista" id="tiempo_entrevista"
                                            class="form-control" value="{{ old('tiempo_entrevista', $entrevista->tiempo_entrevista) }}" min="1">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="titulo">Titulo <span class="text-danger">*</span></label>
                                <input type="text" name="titulo" id="titulo"
                                    class="form-control @error('titulo') is-invalid @enderror"
                                    value="{{ old('titulo', $entrevista->titulo) }}" required maxlength="500">
                                @error('titulo')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="anotaciones">Anotaciones</label>
                                <textarea name="anotaciones" id="anotaciones" class="form-control" rows="3">{{ old('anotaciones', $entrevista->anotaciones) }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Entrevista Virtual</label>
                                        <div>
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" id="es_virtual_no" name="es_virtual" value="0" class="custom-control-input" {{ old('es_virtual', $entrevista->es_virtual) == 0 ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="es_virtual_no">No</label>
                                            </div>
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" id="es_virtual_si" name="es_virtual" value="1" class="custom-control-input" {{ old('es_virtual', $entrevista->es_virtual) == 1 ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="es_virtual_si">Si</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Incluye NNA</label>
                                        <div>
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" id="nna_no" name="nna" value="0" class="custom-control-input" {{ old('nna', $entrevista->nna) == 0 ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="nna_no">No</label>
                                            </div>
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" id="nna_si" name="nna" value="1" class="custom-control-input" {{ old('nna', $entrevista->nna) == 1 ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="nna_si">Si</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Interes Etnico</label>
                                        <div>
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" id="id_etnico_no" name="id_etnico" value="" class="custom-control-input" {{ empty(old('id_etnico', $entrevista->id_etnico)) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="id_etnico_no">No</label>
                                            </div>
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" id="id_etnico_si" name="id_etnico" value="1" class="custom-control-input" {{ old('id_etnico', $entrevista->id_etnico) == 1 ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="id_etnico_si">Si</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="text-warning mb-3"><i class="fas fa-map-marker-alt"></i> Ubicacion y Hechos</h5>

                            <div class="form-group">
                                <label for="id_territorio">Territorio</label>
                                <select name="id_territorio" id="id_territorio" class="form-control">
                                    @foreach($territorios as $id => $nombre)
                                    <option value="{{ $id }}" {{ old('id_territorio', $entrevista->id_territorio) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="entrevista_lugar">Lugar de la Entrevista (Municipio)</label>
                                <select name="entrevista_lugar" id="entrevista_lugar" class="form-control">
                                    @foreach($municipios as $id => $nombre)
                                    <option value="{{ $id }}" {{ old('entrevista_lugar', $entrevista->entrevista_lugar) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="hechos_del">Hechos Desde</label>
                                        <input type="date" name="hechos_del" id="hechos_del" class="form-control"
                                            value="{{ old('hechos_del', $entrevista->hechos_del) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="hechos_al">Hechos Hasta</label>
                                        <input type="date" name="hechos_al" id="hechos_al" class="form-control"
                                            value="{{ old('hechos_al', $entrevista->hechos_al) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="hechos_lugar">Lugar de los Hechos (Municipio)</label>
                                <select name="hechos_lugar" id="hechos_lugar" class="form-control">
                                    @foreach($municipios as $id => $nombre)
                                    <option value="{{ $id }}" {{ old('hechos_lugar', $entrevista->hechos_lugar) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="callout callout-secondary">
                                <h6><i class="fas fa-user-tie"></i> Entrevistador</h6>
                                <p class="mb-0">
                                    @if($entrevista->rel_entrevistador && $entrevista->rel_entrevistador->rel_usuario)
                                        <strong>{{ $entrevista->rel_entrevistador->rel_usuario->name }}</strong><br>
                                        <small class="text-muted">No. {{ str_pad($entrevista->rel_entrevistador->numero_entrevistador ?? 0, 4, '0', STR_PAD_LEFT) }}</small>
                                    @else
                                        <span class="text-muted">Sin asignar</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Actualizar Entrevista
                    </button>
                    <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
