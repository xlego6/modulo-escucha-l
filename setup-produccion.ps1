#
# Script de Configuracion para Produccion - Modulo Escucha Lite
# PowerShell version para Windows Server
#

param(
    [switch]$SkipDocker,
    [switch]$SkipComposer
)

$ErrorActionPreference = "Stop"

# Configurar encoding para caracteres especiales
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# Directorio del script
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ScriptDir

function Write-ColorOutput {
    param([string]$Message, [string]$Color = "White")
    Write-Host $Message -ForegroundColor $Color
}

function Generate-Password {
    $bytes = New-Object byte[] 24
    [System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    return [Convert]::ToBase64String($bytes) -replace '[/+=]', '' | Select-Object -First 1
}

function Test-ValidUrl {
    param([string]$Url)
    return $Url -match '^https?://'
}

Write-ColorOutput "================================================" "Cyan"
Write-ColorOutput "  Configuracion de Produccion - Modulo Escucha  " "Cyan"
Write-ColorOutput "================================================" "Cyan"
Write-Host ""

# ============================================
# PASO 1: Recopilar informacion
# ============================================
Write-ColorOutput "[1/8] Configuracion inicial" "Yellow"
Write-Host ""

# URL de la aplicacion
do {
    $AppUrl = Read-Host "URL de la aplicacion (ej: https://testimonios.ejemplo.com)"
    if (-not (Test-ValidUrl $AppUrl)) {
        Write-ColorOutput "URL invalida. Debe comenzar con http:// o https://" "Red"
    }
} while (-not (Test-ValidUrl $AppUrl))

# Password de base de datos
Write-Host ""
Write-Host "Password para PostgreSQL:"
Write-Host "  1) Generar automaticamente (recomendado)"
Write-Host "  2) Ingresar manualmente"
$DbPassOption = Read-Host "Opcion [1]"
if ([string]::IsNullOrEmpty($DbPassOption)) { $DbPassOption = "1" }

if ($DbPassOption -eq "1") {
    $DbPassword = Generate-Password
    Write-ColorOutput "Password generada: $DbPassword" "Green"
    Write-ColorOutput "IMPORTANTE: Guarda esta password en un lugar seguro" "Yellow"
} else {
    $SecurePass = Read-Host "Ingresa la password de PostgreSQL" -AsSecureString
    $SecurePassConfirm = Read-Host "Confirma la password" -AsSecureString

    $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($SecurePass)
    $DbPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)

    $BSTR2 = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($SecurePassConfirm)
    $DbPasswordConfirm = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR2)

    if ($DbPassword -ne $DbPasswordConfirm) {
        Write-ColorOutput "Las passwords no coinciden. Abortando." "Red"
        exit 1
    }
}

# HuggingFace Token
Write-Host ""
Write-Host "Token de HuggingFace (para diarizacion de audio):"
Write-Host "  - Dejar vacio para deshabilitar diarizacion"
Write-Host "  - Obtener token en: https://huggingface.co/settings/tokens"
$HfToken = Read-Host "HF_TOKEN [dejar vacio para omitir]"

# ============================================
# PASO 2: Backup de archivos originales
# ============================================
Write-Host ""
Write-ColorOutput "[2/8] Creando backup de configuracion actual" "Yellow"

$BackupDir = "backups\config_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null

if (Test-Path "docker-compose.yml") {
    Copy-Item "docker-compose.yml" "$BackupDir\"
}
if (Test-Path "www\.env") {
    Copy-Item "www\.env" "$BackupDir\www.env"
}
if (Test-Path "www\.env.example") {
    Copy-Item "www\.env.example" "$BackupDir\www.env.example"
}

Write-ColorOutput "Backup creado en: $BackupDir" "Green"

# ============================================
# PASO 3: Configurar docker-compose.yml
# ============================================
Write-Host ""
Write-ColorOutput "[3/8] Configurando docker-compose.yml" "Yellow"

$DockerCompose = Get-Content "docker-compose.yml" -Raw

# Cambiar password de PostgreSQL
$DockerCompose = $DockerCompose -replace 'POSTGRES_PASSWORD:\s*\S+', "POSTGRES_PASSWORD: $DbPassword"

# Configurar HF_TOKEN
if ([string]::IsNullOrEmpty($HfToken)) {
    $DockerCompose = $DockerCompose -replace '- HF_TOKEN=\S*', '- HF_TOKEN='
} else {
    $DockerCompose = $DockerCompose -replace '- HF_TOKEN=\S*', "- HF_TOKEN=$HfToken"
}

# Comentar puertos expuestos innecesariamente
$DockerCompose = $DockerCompose -replace '(\s+)- 5556:5432', '$1# - 5556:5432  # Comentado por seguridad'
$DockerCompose = $DockerCompose -replace '(\s+)- 5000:5000', '$1# - 5000:5000  # Comentado por seguridad'
$DockerCompose = $DockerCompose -replace '(\s+)- 5001:5001', '$1# - 5001:5001  # Comentado por seguridad'
$DockerCompose = $DockerCompose -replace '(\s+)- 6379:6379', '$1# - 6379:6379  # Comentado por seguridad'

$DockerCompose | Set-Content "docker-compose.yml" -NoNewline

Write-ColorOutput "docker-compose.yml configurado" "Green"

# ============================================
# PASO 4: Configurar www/.env
# ============================================
Write-Host ""
Write-ColorOutput "[4/8] Configurando www\.env" "Yellow"

# Crear .env desde .env.example si no existe
if (-not (Test-Path "www\.env")) {
    Copy-Item "www\.env.example" "www\.env"
}

$EnvContent = Get-Content "www\.env" -Raw

# Configurar variables de produccion
$EnvContent = $EnvContent -replace 'APP_ENV=\S*', 'APP_ENV=production'
$EnvContent = $EnvContent -replace 'APP_DEBUG=\S*', 'APP_DEBUG=false'
$EnvContent = $EnvContent -replace 'APP_URL=\S*', "APP_URL=$AppUrl"
$EnvContent = $EnvContent -replace 'DB_PASSWORD=\S*', "DB_PASSWORD=$DbPassword"

# Configurar URLs de servicios internos
$EnvContent = $EnvContent -replace 'TRANSCRIPTION_SERVICE_URL=\S*', 'TRANSCRIPTION_SERVICE_URL=http://transcription:5000'
$EnvContent = $EnvContent -replace 'NER_SERVICE_URL=\S*', 'NER_SERVICE_URL=http://ner:5001'

$EnvContent | Set-Content "www\.env" -NoNewline

Write-ColorOutput "www\.env configurado" "Green"

# ============================================
# PASO 5: Iniciar contenedores
# ============================================
Write-Host ""
Write-ColorOutput "[5/8] Iniciando contenedores Docker" "Yellow"

if (-not $SkipDocker) {
    # Detener contenedores existentes
    docker-compose down 2>$null

    # Verificar si existe BD anterior
    if (Test-Path "postgres-data") {
        $DeleteDb = Read-Host "Se encontro una base de datos existente. Eliminar y crear nueva? [s/N]"
        if ($DeleteDb -match '^[Ss]$') {
            Remove-Item -Recurse -Force "postgres-data"
            Write-ColorOutput "Base de datos eliminada. Se creara una nueva." "Yellow"
        }
    }

    # Construir e iniciar
    docker-compose up -d --build

    Write-ColorOutput "Contenedores iniciados" "Green"

    # Esperar a que la BD este lista
    Write-Host "Esperando a que PostgreSQL este listo..."
    Start-Sleep -Seconds 15
} else {
    Write-ColorOutput "Saltando inicio de Docker (--SkipDocker)" "Yellow"
}

# ============================================
# PASO 6: Instalar dependencias y generar key
# ============================================
Write-Host ""
Write-ColorOutput "[6/8] Instalando dependencias PHP" "Yellow"

if (-not $SkipComposer) {
    docker exec mel-app composer install --no-dev --optimize-autoloader
}

Write-Host ""
Write-ColorOutput "[6/8] Generando APP_KEY" "Yellow"

docker exec mel-app php artisan key:generate --force

Write-ColorOutput "APP_KEY generada" "Green"

# ============================================
# PASO 7: Optimizar para produccion
# ============================================
Write-Host ""
Write-ColorOutput "[7/8] Optimizando para produccion" "Yellow"

docker exec mel-app php artisan config:cache
docker exec mel-app php artisan route:cache
docker exec mel-app php artisan view:cache

# Configurar permisos de storage
docker exec mel-app chmod -R 775 /var/www/storage
docker exec mel-app chmod -R 775 /var/www/bootstrap/cache
docker exec mel-app chown -R www-data:www-data /var/www/storage
docker exec mel-app chown -R www-data:www-data /var/www/bootstrap/cache

Write-ColorOutput "Optimizacion completada" "Green"

# ============================================
# PASO 8: Verificacion final
# ============================================
Write-Host ""
Write-ColorOutput "[8/8] Verificacion final" "Yellow"

# Verificar contenedores
Write-Host ""
Write-Host "Estado de contenedores:"
docker-compose ps

# ============================================
# RESUMEN FINAL
# ============================================
Write-Host ""
Write-ColorOutput "================================================" "Cyan"
Write-ColorOutput "          CONFIGURACION COMPLETADA              " "Cyan"
Write-ColorOutput "================================================" "Cyan"
Write-Host ""
Write-ColorOutput "La aplicacion esta lista para produccion." "Green"
Write-Host ""
Write-Host "Credenciales de base de datos:"
Write-Host "  Usuario: dba"
Write-Host "  Password: $DbPassword"
Write-Host ""
Write-Host "Acceso a la aplicacion:"
Write-Host "  URL: $AppUrl"
Write-Host "  Puerto Docker: 8001"
Write-Host ""
Write-Host "Usuario administrador por defecto:"
Write-Host "  Email: admin@example.com"
Write-Host "  Password: password"
Write-Host ""
Write-ColorOutput "IMPORTANTE - Acciones pendientes:" "Red"
Write-Host "  1. Cambiar password del usuario admin inmediatamente"
Write-Host "  2. Configurar proxy inverso (IIS/Nginx) con HTTPS"
Write-Host "  3. Guardar las credenciales en un lugar seguro"
Write-Host ""
Write-Host "Archivo de backup: $BackupDir"
Write-Host ""

# Guardar credenciales en archivo seguro
$CredsFile = "$BackupDir\credenciales.txt"
$CredsContent = @"
===========================================
CREDENCIALES - Modulo Escucha Lite
Generadas: $(Get-Date)
===========================================

Base de Datos PostgreSQL:
  Host: mel-db (interno) / localhost:5556 (si puerto habilitado)
  Database: testimonios
  Usuario: dba
  Password: $DbPassword

Usuario Administrador:
  Email: admin@example.com
  Password: password (CAMBIAR INMEDIATAMENTE)

URL Aplicacion: $AppUrl

HuggingFace Token: $(if ([string]::IsNullOrEmpty($HfToken)) { "No configurado" } else { $HfToken })
===========================================
"@

$CredsContent | Set-Content $CredsFile

Write-ColorOutput "Credenciales guardadas en: $CredsFile" "Yellow"
Write-ColorOutput "Protege este archivo adecuadamente." "Yellow"

# ============================================
# Configurar Firewall de Windows
# ============================================
Write-Host ""
$ConfigFirewall = Read-Host "Configurar reglas de Firewall de Windows? [S/n]"
if ($ConfigFirewall -notmatch '^[Nn]$') {
    Write-ColorOutput "Configurando Firewall..." "Yellow"

    # Abrir puerto 8001 para la aplicacion web
    New-NetFirewallRule -DisplayName "Modulo Escucha Web (8001)" -Direction Inbound -Port 8001 -Protocol TCP -Action Allow -ErrorAction SilentlyContinue

    Write-ColorOutput "Regla de Firewall creada para puerto 8001" "Green"
}

Write-Host ""
Write-ColorOutput "Instalacion completada exitosamente!" "Green"
