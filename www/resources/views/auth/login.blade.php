<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MÓDULO DE ESCUCHA CNMH</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .login-page {
            background: #5e8f9b;
            padding-bottom: 20px;
        }
        .login-logo-img {
            max-width: 200px;
            margin-bottom: 20px;
            filter: brightness(0) invert(1);
        }
        .login-logo-subtitle {
            font-family: 'Barlow', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 1px;
            color: #ffffff;
        }
        .login-bandera {
            position: fixed;
            right: 0;
            top: 40px;
            height: 200px;
            width: auto;
            pointer-events: none;
        }
        /* Tamaño según el espacio libre a la derecha del formulario (450px) */
        @media (max-width: 1699.98px) {
            .login-bandera { height: 140px; }
        }
        @media (max-width: 1299.98px) {
            .login-bandera { height: 100px; }
        }
        @media (max-width: 1059.98px) {
            .login-bandera { display: none; }
        }
        .barra-colores {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            height: 20px;
            display: flex;
            background: #f3b933;
            gap: 14px;
        }
        .barra-colores span {
            flex: 1;
        }
        .login-logo-title {
            font-family: 'Barlow', sans-serif;
            font-weight: 700;
            font-size: 2.6rem;
            color: #ffffff;
            line-height: 1.2;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .login-box {
            width: 450px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .btn-primary {
            background-color: #5e8f9b;
            border-color: #5e8f9b;
            color: #ffffff;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #4d7682;
            border-color: #4d7682;
            color: #ffffff;
        }
        .login-box-msg {
            font-family: 'Barlow', sans-serif;
        }
    </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo mb-4 text-center">
        <img src="{{ asset('img/logo-cnmh.png') }}" alt="CNMH" class="login-logo-img">
        <br>
        <span class="login-logo-title">MÓDULO DE ESCUCHA<br>CNMH</span>
        <br>
        <small class="login-logo-subtitle">Sistema de escucha CNMH</small>
    </div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Inicie sesion para acceder al sistema</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form id="loginForm" action="{{ route('login') }}" method="post">
                @csrf
                <div class="input-group mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Correo electronico" value="{{ old('email') }}" required autofocus>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Contrasena" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Recordarme</label>
                        </div>
                    </div>
                    <div class="col-4">
                        <button id="loginSubmitBtn" type="submit" class="btn btn-primary btn-block">Ingresar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<img src="{{ asset('header-footer/bandera-cuadros.png') }}" alt="" aria-hidden="true" class="login-bandera">
<div class="barra-colores" aria-hidden="true">
    <span style="background:#68babb"></span>
    <span style="background:#78a4a6"></span>
    <span style="background:#84b3d6"></span>
    <span style="background:#5a888d"></span>
    <span style="background:#8b88ad"></span>
    <span style="background:#e37fab"></span>
    <span style="background:#c72928"></span>
    <span style="background:#e57430"></span>
    <span style="background:#eb9857"></span>
    <span style="background:#e0ab49"></span>
    <span style="background:#96af76"></span>
    <span style="background:#42a141"></span>
    <span style="background:#827f6a"></span>
    <span style="background:#9a8c72"></span>
    <span style="background:#a78565"></span>
    <span style="background:#a0685b"></span>
    <span style="background:#8d6541"></span>
    <span style="background:#c08f32"></span>
    <span style="background:#caba9b"></span>
    <span style="background:#ddd1bd"></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('loginForm');
    var submitBtn = document.getElementById('loginSubmitBtn');

    if (!form || !submitBtn) {
        return;
    }

    form.addEventListener('submit', function () {
        if (form.dataset.submitted === '1') {
            return false;
        }

        form.dataset.submitted = '1';
        submitBtn.disabled = true;
        submitBtn.innerText = 'Ingresando...';
    });
});
</script>
</body>
</html>
