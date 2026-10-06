@extends('layouts.app')

@section('title', 'Nueva persona')
@section('content_header', 'Registrar nueva persona')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Datos de la persona</h3>
            </div>
            <form action="{{ route('personas.store') }}" method="POST">
                @csrf
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
                            <h5 class="seccion-titulo"><i class="fas fa-user"></i> Identificación</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre">Nombres <span class="text-danger">*</span></label>
                                        <input type="text" name="nombre" id="nombre"
                                            class="form-control @error('nombre') is-invalid @enderror"
                                            value="{{ old('nombre') }}" required maxlength="200">
                                        @error('nombre')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="apellido">Apellidos <span class="text-danger">*</span></label>
                                        <input type="text" name="apellido" id="apellido"
                                            class="form-control @error('apellido') is-invalid @enderror"
                                            value="{{ old('apellido') }}" required maxlength="200">
                                        @error('apellido')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="alias">Alias / nombre identitario</label>
                                <input type="text" name="alias" id="alias" class="form-control"
                                    value="{{ old('alias') }}" maxlength="100" placeholder="Opcional">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_tipo_documento">Tipo de documento</label>
                                        <select name="id_tipo_documento" id="id_tipo_documento" class="form-control">
                                            @foreach($tipos_documento as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_tipo_documento') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="num_documento">Número de documento</label>
                                        <input type="text" name="num_documento" id="num_documento" class="form-control"
                                            value="{{ old('num_documento') }}" maxlength="50">
                                    </div>
                                </div>
                            </div>

                            <h5 class="seccion-titulo"><i class="fas fa-birthday-cake"></i> Fecha de nacimiento</h5>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fec_nac_d">Día</label>
                                        <input type="number" name="fec_nac_d" id="fec_nac_d" class="form-control"
                                            value="{{ old('fec_nac_d') }}" min="1" max="31" placeholder="DD">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fec_nac_m">Mes</label>
                                        <input type="number" name="fec_nac_m" id="fec_nac_m" class="form-control"
                                            value="{{ old('fec_nac_m') }}" min="1" max="12" placeholder="MM">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fec_nac_a">Año</label>
                                        <input type="number" name="fec_nac_a" id="fec_nac_a" class="form-control"
                                            value="{{ old('fec_nac_a') }}" min="1900" max="{{ date('Y') }}" placeholder="AAAA">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_lugar_nacimiento_depto">Departamento nacimiento</label>
                                        <select name="id_lugar_nacimiento_depto" id="id_lugar_nacimiento_depto" class="form-control">
                                            @foreach($departamentos as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_lugar_nacimiento_depto') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_lugar_nacimiento">Municipio nacimiento</label>
                                        <select name="id_lugar_nacimiento" id="id_lugar_nacimiento" class="form-control">
                                            @foreach($municipios as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_lugar_nacimiento') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="seccion-titulo"><i class="fas fa-venus-mars"></i> Caracterización</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_sexo">Sexo</label>
                                        <select name="id_sexo" id="id_sexo" class="form-control">
                                            @foreach($sexos as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_sexo') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_etnia">Grupo étnico</label>
                                        <select name="id_etnia" id="id_etnia" class="form-control">
                                            @foreach($etnias as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_etnia') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <h5 class="seccion-titulo"><i class="fas fa-home"></i> Residencia</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_lugar_residencia_depto">Departamento residencia</label>
                                        <select name="id_lugar_residencia_depto" id="id_lugar_residencia_depto" class="form-control">
                                            @foreach($departamentos as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_lugar_residencia_depto') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_lugar_residencia_muni">Municipio residencia</label>
                                        <select name="id_lugar_residencia_muni" id="id_lugar_residencia_muni" class="form-control">
                                            @foreach($municipios as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_lugar_residencia_muni') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <h5 class="seccion-titulo"><i class="fas fa-phone"></i> Contacto</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="telefono">Teléfono</label>
                                        <input type="text" name="telefono" id="telefono" class="form-control"
                                            value="{{ old('telefono') }}" maxlength="50" placeholder="Ej: 3001234567">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="correo_electronico">Correo electrónico</label>
                                        <input type="email" name="correo_electronico" id="correo_electronico" class="form-control"
                                            value="{{ old('correo_electronico') }}" maxlength="100" placeholder="correo@ejemplo.com">
                                    </div>
                                </div>
                            </div>

                            <h5 class="seccion-titulo"><i class="fas fa-briefcase"></i> Ocupación</h5>

                            <div class="form-group">
                                <label for="ocupacion_actual">Ocupación actual</label>
                                <input type="text" name="ocupacion_actual" id="ocupacion_actual" class="form-control"
                                    value="{{ old('ocupacion_actual') }}" maxlength="200" placeholder="Actividad principal">
                            </div>

                            <div class="form-group">
                                <label for="profesion">Profesión</label>
                                <input type="text" name="profesion" id="profesion" class="form-control"
                                    value="{{ old('profesion') }}" maxlength="200" placeholder="Título o formación">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar persona
                    </button>
                    <a href="{{ route('personas.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
