@extends('layouts.app')

@section('title', 'Dashboard - Testimonios')
@section('content_header', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['total_entrevistas'] ?? 0 }}</h3>
                <p>Total Entrevistas</p>
            </div>
            <div class="icon">
                <i class="fas fa-microphone"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['total_personas'] ?? 0 }}</h3>
                <p>Personas Registradas</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['total_adjuntos'] ?? 0 }}</h3>
                <p>Archivos Adjuntos</p>
            </div>
            <div class="icon">
                <i class="fas fa-paperclip"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['entrevistas_mes'] ?? 0 }}</h3>
                <p>Entrevistas este Mes</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ultimas Entrevistas</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Titulo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ultimas_entrevistas as $entrevista)
                        <tr>
                            <td>{{ $entrevista->fmt_codigo }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($entrevista->fmt_titulo, 50) }}</td>
                            <td>{{ $entrevista->fmt_fecha }}</td>
                            <td>
                                @if($entrevista->id_activo == 1)
                                    <span class="badge badge-success">Activa</span>
                                @else
                                    <span class="badge badge-secondary">Inactiva</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No hay entrevistas registradas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
