<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Testimonios')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    @yield('css')
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('home') }}" class="nav-link">Inicio</a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i> {{ Auth::user()->name ?? 'Usuario' }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('perfil') }}" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Mi Perfil
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesion
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('home') }}" class="brand-link">
            <span class="brand-text font-weight-light">Testimonios</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="#" class="d-block">{{ Auth::user()->name ?? 'Usuario' }}</a>
                </div>
            </div>
            @php $nivel = Auth::user()->id_nivel ?? 99; @endphp
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
                    {{-- Inicio: Todos --}}
                    <li class="nav-item">
                        <a href="{{ route('home') }}" class="nav-link">
                            <i class="nav-icon fas fa-home"></i>
                            <p>Inicio</p>
                        </a>
                    </li>

                    {{-- Entrevistas: Admin(1), Líder(2), Entrevistador(3) --}}
                    @if(in_array($nivel, [1, 2, 3]))
                    <li class="nav-item">
                        <a href="{{ route('entrevistas.index') }}" class="nav-link {{ request()->routeIs('entrevistas.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-microphone"></i>
                            <p>Entrevistas</p>
                        </a>
                    </li>
                    @endif

                    {{-- Personas: Admin(1), Entrevistador(3) --}}
                    @if(in_array($nivel, [1, 3]))
                    <li class="nav-item">
                        <a href="{{ route('personas.index') }}" class="nav-link {{ request()->routeIs('personas.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Personas</p>
                        </a>
                    </li>
                    @endif

                    {{-- Buscadora: Admin(1), Líder(2), Entrevistador(3) --}}
                    @if(in_array($nivel, [1, 2, 3]))
                    <li class="nav-item">
                        <a href="{{ route('buscador.index') }}" class="nav-link {{ request()->routeIs('buscador.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-search"></i>
                            <p>Buscadora</p>
                        </a>
                    </li>
                    @endif

                    {{-- Estadísticas: Admin(1), Líder(2) --}}
                    @if(in_array($nivel, [1, 2]))
                    <li class="nav-item">
                        <a href="{{ route('estadisticas.index') }}" class="nav-link {{ request()->routeIs('estadisticas.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Estadisticas</p>
                        </a>
                    </li>
                    @endif

                    {{-- Mapa: Admin(1), Líder(2) --}}
                    @if(in_array($nivel, [1, 2]))
                    <li class="nav-item">
                        <a href="{{ route('mapa.index') }}" class="nav-link {{ request()->routeIs('mapa.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-map-marked-alt"></i>
                            <p>Mapa</p>
                        </a>
                    </li>
                    @endif

                    {{-- Exportar Excel: Admin(1), Líder(2) --}}
                    @if(in_array($nivel, [1, 2]))
                    <li class="nav-item">
                        <a href="{{ route('exportar.index') }}" class="nav-link {{ request()->routeIs('exportar.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-excel"></i>
                            <p>Exportar Excel</p>
                        </a>
                    </li>
                    @endif

                    {{-- Procesamientos: Admin(1), Líder(2), Transcriptor(4) --}}
                    @if(in_array($nivel, [1, 2, 4]))
                    <li class="nav-item has-treeview {{ request()->routeIs('procesamientos.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('procesamientos.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>
                                Procesamientos
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            {{-- Centro de Control: Admin(1), Líder(2) --}}
                            @if(in_array($nivel, [1, 2]))
                            <li class="nav-item">
                                <a href="{{ route('procesamientos.index') }}" class="nav-link {{ request()->routeIs('procesamientos.index') ? 'active' : '' }}">
                                    <i class="fas fa-tachometer-alt nav-icon"></i>
                                    <p>Centro de Control</p>
                                </a>
                            </li>
                            @endif

                            {{-- Transcripción: Admin(1), Líder(2) --}}
                            @if(in_array($nivel, [1, 2]))
                            <li class="nav-item">
                                <a href="{{ route('procesamientos.transcripcion') }}" class="nav-link {{ request()->routeIs('procesamientos.transcripcion') ? 'active' : '' }}">
                                    <i class="fas fa-microphone nav-icon"></i>
                                    <p>Transcripcion</p>
                                </a>
                            </li>
                            @endif

                            {{-- Edición: Admin(1), Líder(2), Transcriptor(4) --}}
                            <li class="nav-item">
                                <a href="{{ route('procesamientos.edicion') }}" class="nav-link {{ request()->routeIs('procesamientos.edicion') || request()->routeIs('procesamientos.editar-transcripcion') ? 'active' : '' }}">
                                    <i class="fas fa-edit nav-icon"></i>
                                    <p>Edicion</p>
                                </a>
                            </li>

                            {{-- Entidades: Admin(1), Líder(2) --}}
                            @if(in_array($nivel, [1, 2]))
                            <li class="nav-item">
                                <a href="{{ route('procesamientos.entidades') }}" class="nav-link {{ request()->routeIs('procesamientos.entidades') || request()->routeIs('procesamientos.ver-entidades') ? 'active' : '' }}">
                                    <i class="fas fa-tags nav-icon"></i>
                                    <p>Entidades</p>
                                </a>
                            </li>
                            @endif

                            {{-- Anonimización: Admin(1), Líder(2), Transcriptor(4) --}}
                            @if(in_array($nivel, [1, 2, 4]))
                            <li class="nav-item">
                                <a href="{{ route('procesamientos.anonimizacion') }}" class="nav-link {{ request()->routeIs('procesamientos.anonimizacion') || request()->routeIs('procesamientos.previsualizar-anonimizacion') || request()->routeIs('procesamientos.editar-anonimizacion-asignada') ? 'active' : '' }}">
                                    <i class="fas fa-user-secret nav-icon"></i>
                                    <p>Anonimizacion</p>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    {{-- Administración: Admin(1), Líder(2) --}}
                    @if(in_array($nivel, [1, 2]))
                    <li class="nav-item has-treeview {{ request()->routeIs('usuarios.*') || request()->routeIs('permisos.*') || request()->routeIs('catalogos.*') || request()->routeIs('traza.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('usuarios.*') || request()->routeIs('permisos.*') || request()->routeIs('catalogos.*') || request()->routeIs('traza.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tools"></i>
                            <p>
                                Administracion
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            {{-- Usuarios: Solo Admin(1) --}}
                            @if($nivel == 1)
                            <li class="nav-item">
                                <a href="{{ route('usuarios.index') }}" class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                                    <i class="fas fa-users-cog nav-icon"></i>
                                    <p>Usuarios</p>
                                </a>
                            </li>
                            @endif

                            {{-- Permisos: Admin(1), Líder(2) --}}
                            <li class="nav-item">
                                <a href="{{ route('permisos.index') }}" class="nav-link {{ request()->routeIs('permisos.index') || request()->routeIs('permisos.create') || request()->routeIs('permisos.show') ? 'active' : '' }}">
                                    <i class="fas fa-key nav-icon"></i>
                                    <p>Permisos</p>
                                </a>
                            </li>

                            {{-- Desclasificación: Solo Admin(1) --}}
                            @if($nivel == 1)
                            <li class="nav-item">
                                <a href="{{ route('permisos.desclasificar') }}" class="nav-link {{ request()->routeIs('permisos.desclasificar') ? 'active' : '' }}">
                                    <i class="fas fa-unlock-alt nav-icon"></i>
                                    <p>Desclasificacion</p>
                                </a>
                            </li>
                            @endif

                            {{-- Accesos Otorgados: Admin(1), Líder(2) --}}
                            <li class="nav-item">
                                <a href="{{ route('permisos.accesos_otorgados') }}" class="nav-link {{ request()->routeIs('permisos.accesos_otorgados') ? 'active' : '' }}">
                                    <i class="fas fa-check-circle nav-icon"></i>
                                    <p>Accesos Otorgados</p>
                                </a>
                            </li>

                            {{-- Catálogos: Admin(1), Líder(2) --}}
                            <li class="nav-item">
                                <a href="{{ route('catalogos.index') }}" class="nav-link {{ request()->routeIs('catalogos.*') ? 'active' : '' }}">
                                    <i class="fas fa-list-alt nav-icon"></i>
                                    <p>Catalogos</p>
                                </a>
                            </li>

                            {{-- Traza: Admin(1), Líder(2) --}}
                            <li class="nav-item">
                                <a href="{{ route('traza.index') }}" class="nav-link {{ request()->routeIs('traza.*') ? 'active' : '' }}">
                                    <i class="fas fa-history nav-icon"></i>
                                    <p>Traza de Actividad</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    {{-- Ayuda: Todos los usuarios --}}
                    <li class="nav-item">
                        <a href="{{ route('ayuda.index') }}" class="nav-link {{ request()->routeIs('ayuda.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-question-circle"></i>
                            <p>Ayuda</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>@yield('content_header', 'Dashboard')</h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                @include('flash::message')
                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>Testimonios Lite</strong> - Sistema de Gestion de Entrevistas
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
@yield('scripts')
@yield('js')
</body>
</html>
