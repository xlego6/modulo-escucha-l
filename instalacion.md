# Manual de Instalacion - Modulo Escucha Lite

Sistema de gestion de testimonios para el Centro Nacional de Memoria Historica de Colombia.

---

## Tabla de Contenidos

1. [Requisitos del Sistema](#requisitos-del-sistema)
2. [Instalacion Rapida (Desarrollo)](#instalacion-rapida-desarrollo)
3. [Instalacion en Servidor Linux (Produccion)](#instalacion-en-servidor-produccion)
4. [Instalacion en Windows Server](#instalacion-en-windows-server)
5. [Configuracion de Servicios de Procesamiento](#configuracion-de-servicios-de-procesamiento)
6. [Configuracion Avanzada](#configuracion-avanzada)
7. [Solucion de Problemas](#solucion-de-problemas)

---

## Requisitos del Sistema

### Para la Aplicacion Web (Docker)
- Docker Engine 20.10+
- Docker Compose 2.0+
- 2 GB RAM minimo (4 GB recomendado)
- 10 GB espacio en disco

### Para Servicios de Procesamiento (Opcional)
- Python 3.9+
- NVIDIA GPU con CUDA 11.7+ (para transcripcion con GPU)
- 8 GB RAM minimo (16 GB recomendado para transcripcion)

---

## Instalacion Rapida (Desarrollo)

### Paso 1: Clonar o copiar el proyecto

```bash
# Si tienes git
git clone <repositorio> modulo-escucha-lite
cd modulo-escucha-lite

# O simplemente copia la carpeta modulo-escucha-lite a tu ubicacion deseada
```

### Paso 2: Configurar el archivo .env

```bash
cd www
cp .env.example .env
```

Edita `www/.env` si necesitas cambiar la configuracion:

```env
APP_NAME=Testimonios
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8001

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=testimonios
DB_USERNAME=dba
DB_PASSWORD=sql
```

### Paso 3: Iniciar los contenedores Docker

```bash
cd modulo-escucha-lite
docker-compose up -d
```

Esto iniciara 3 contenedores:
- `mel-web` - Servidor web Nginx (puerto 8001)
- `mel-app` - PHP-FPM 8.1
- `mel-db` - PostgreSQL 11 (puerto 5556)

### Paso 4: Instalar dependencias PHP

```bash
docker exec -it mel-app composer install
```

### Paso 5: Generar clave de aplicacion

```bash
docker exec -it mel-app php artisan key:generate
```

### Paso 6: Acceder a la aplicacion

Abre en tu navegador: **http://localhost:8001**

Usuario por defecto:
- **Email:** admin@example.com
- **Password:** password

---

## Instalacion en Servidor (Produccion)

### Requisitos del Servidor
- Ubuntu 20.04/22.04 LTS o Debian 11+
- Docker y Docker Compose instalados
- Dominio configurado (opcional, para HTTPS)
- Puertos 80/443 disponibles

### Paso 1: Preparar el servidor

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Docker (si no esta instalado)
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Instalar Docker Compose
sudo apt install docker-compose-plugin -y

# Cerrar sesion y volver a entrar para aplicar grupo docker
```

### Paso 2: Copiar archivos al servidor

```bash
# Desde tu maquina local
scp -r modulo-escucha-lite usuario@servidor:/home/usuario/

# O usar rsync para mejor rendimiento
rsync -avz --progress modulo-escucha-lite usuario@servidor:/home/usuario/
```

### Paso 3: Configurar para produccion

Edita `www/.env`:

```env
APP_NAME=Testimonios
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=testimonios
DB_USERNAME=dba
DB_PASSWORD=CAMBIA_ESTA_PASSWORD_SEGURA
```

Edita `docker-compose.yml` para cambiar la password de PostgreSQL:

```yaml
db:
    environment:
        POSTGRES_USER: dba
        POSTGRES_PASSWORD: CAMBIA_ESTA_PASSWORD_SEGURA
        POSTGRES_DB: testimonios
```

### Paso 4: Configurar puertos (opcional)

Para usar puerto 80 en lugar de 8001, edita `docker-compose.yml`:

```yaml
web:
    ports:
        - 80:80
```

### Paso 5: Iniciar la aplicacion

```bash
cd modulo-escucha-lite
docker-compose up -d
docker exec -it mel-app composer install --no-dev --optimize-autoloader
docker exec -it mel-app php artisan key:generate
docker exec -it mel-app php artisan config:cache
docker exec -it mel-app php artisan route:cache
docker exec -it mel-app php artisan view:cache
```

### Paso 6: Configurar HTTPS con Certbot (Recomendado)

Para produccion, se recomienda usar un proxy inverso como Nginx o Traefik en el host con certificado SSL.

**Opcion A: Nginx en el host como proxy**

```bash
# Instalar Nginx y Certbot
sudo apt install nginx certbot python3-certbot-nginx -y

# Crear configuracion
sudo nano /etc/nginx/sites-available/testimonios
```

```nginx
server {
    server_name tu-dominio.com;

    location / {
        proxy_pass http://127.0.0.1:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    client_max_body_size 100M;
}
```

```bash
# Habilitar sitio
sudo ln -s /etc/nginx/sites-available/testimonios /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Obtener certificado SSL
sudo certbot --nginx -d tu-dominio.com
```

---

## Instalacion en Windows Server

### Requisitos del Servidor Windows

- Windows Server 2019 o 2022
- Docker Desktop for Windows (con WSL 2) o Docker Engine
- 4 GB RAM minimo (8 GB recomendado)
- 20 GB espacio en disco
- Permisos de administrador

### Opcion A: Instalacion con Docker Desktop (Recomendado)

#### Paso 1: Instalar WSL 2

Abrir PowerShell como Administrador:

```powershell
# Habilitar WSL
dism.exe /online /enable-feature /featurename:Microsoft-Windows-Subsystem-Linux /all /norestart

# Habilitar Virtual Machine Platform
dism.exe /online /enable-feature /featurename:VirtualMachinePlatform /all /norestart

# Reiniciar el servidor
Restart-Computer
```

Despues de reiniciar, actualizar el kernel WSL:

```powershell
# Descargar e instalar el paquete de actualizacion del kernel WSL 2
# Descargar desde: https://aka.ms/wsl2kernel
# O usar winget:
winget install Microsoft.WSL

# Establecer WSL 2 como version predeterminada
wsl --set-default-version 2

# Instalar una distribucion Linux (Ubuntu recomendado)
wsl --install -d Ubuntu
```

#### Paso 2: Instalar Docker Desktop

1. Descargar Docker Desktop desde: https://www.docker.com/products/docker-desktop/
2. Ejecutar el instalador como Administrador
3. Durante la instalacion, asegurar que "Use WSL 2 instead of Hyper-V" este seleccionado
4. Reiniciar el servidor cuando se solicite

Verificar instalacion:

```powershell
docker --version
docker-compose --version
```

#### Paso 3: Configurar Docker Desktop

1. Abrir Docker Desktop
2. Ir a Settings > Resources > WSL Integration
3. Habilitar la integracion con Ubuntu
4. Ir a Settings > General
5. Asegurar que "Use the WSL 2 based engine" este habilitado

#### Paso 4: Copiar archivos del proyecto

```powershell
# Crear directorio para el proyecto
mkdir C:\Apps\modulo-escucha-lite

# Copiar archivos (desde una ubicacion local o red)
Copy-Item -Recurse "\\servidor\compartido\modulo-escucha-lite\*" "C:\Apps\modulo-escucha-lite\"

# O descomprimir si viene en ZIP
Expand-Archive -Path "modulo-escucha-lite.zip" -DestinationPath "C:\Apps\"
```

#### Paso 5: Configurar variables de entorno

Editar `C:\Apps\modulo-escucha-lite\www\.env`:

```env
APP_NAME=Testimonios
APP_ENV=production
APP_DEBUG=false
APP_URL=http://nombre-servidor:8001

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=testimonios
DB_USERNAME=dba
DB_PASSWORD=CAMBIA_ESTA_PASSWORD_SEGURA
```

#### Paso 6: Iniciar la aplicacion

```powershell
cd C:\Apps\modulo-escucha-lite

# Iniciar contenedores
docker-compose up -d

# Verificar que estan corriendo
docker-compose ps

# Instalar dependencias PHP
docker exec -it mel-app composer install --no-dev --optimize-autoloader

# Generar clave de aplicacion
docker exec -it mel-app php artisan key:generate

# Optimizar para produccion
docker exec -it mel-app php artisan config:cache
docker exec -it mel-app php artisan route:cache
docker exec -it mel-app php artisan view:cache
```

#### Paso 7: Configurar Firewall de Windows

```powershell
# Abrir puerto 8001 para la aplicacion web
New-NetFirewallRule -DisplayName "Modulo Escucha Web" -Direction Inbound -Port 8001 -Protocol TCP -Action Allow

# Abrir puerto 5556 para PostgreSQL (solo si se necesita acceso externo)
New-NetFirewallRule -DisplayName "Modulo Escucha DB" -Direction Inbound -Port 5556 -Protocol TCP -Action Allow

# Opcionalmente abrir puertos para servicios de procesamiento
New-NetFirewallRule -DisplayName "Transcripcion Service" -Direction Inbound -Port 5000 -Protocol TCP -Action Allow
New-NetFirewallRule -DisplayName "NER Service" -Direction Inbound -Port 5001 -Protocol TCP -Action Allow
```

#### Paso 8: Configurar inicio automatico

Crear tarea programada para iniciar Docker y la aplicacion:

```powershell
# Crear script de inicio
$script = @"
Start-Sleep -Seconds 30
cd C:\Apps\modulo-escucha-lite
docker-compose up -d
"@

$script | Out-File -FilePath "C:\Apps\start-testimonios.ps1" -Encoding UTF8

# Crear tarea programada
$action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-ExecutionPolicy Bypass -File C:\Apps\start-testimonios.ps1"
$trigger = New-ScheduledTaskTrigger -AtStartup
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

Register-ScheduledTask -TaskName "Iniciar Modulo Escucha" -Action $action -Trigger $trigger -Principal $principal -Settings $settings
```

### Opcion B: Instalacion con Docker Engine (sin Docker Desktop)

Para servidores sin interfaz grafica o donde Docker Desktop no es viable.

#### Paso 1: Habilitar caracteristicas de Windows

```powershell
# Instalar Hyper-V y Containers
Install-WindowsFeature -Name Hyper-V -IncludeManagementTools -Restart
Install-WindowsFeature -Name Containers
```

#### Paso 2: Instalar Docker Engine

```powershell
# Instalar el proveedor de Docker
Install-Module -Name DockerMsftProvider -Repository PSGallery -Force

# Instalar Docker
Install-Package -Name docker -ProviderName DockerMsftProvider -Force

# Reiniciar el servidor
Restart-Computer
```

Despues de reiniciar:

```powershell
# Iniciar el servicio Docker
Start-Service Docker

# Configurar inicio automatico
Set-Service -Name Docker -StartupType Automatic

# Verificar instalacion
docker version
```

#### Paso 3: Instalar Docker Compose

```powershell
# Descargar Docker Compose
$composeVersion = "v2.24.0"
$url = "https://github.com/docker/compose/releases/download/$composeVersion/docker-compose-windows-x86_64.exe"

Invoke-WebRequest -Uri $url -OutFile "$env:ProgramFiles\Docker\docker-compose.exe"

# Verificar instalacion
docker-compose --version
```

#### Paso 4: Continuar con los pasos 4-8 de la Opcion A

Los pasos restantes son identicos a la Opcion A (copiar archivos, configurar, iniciar contenedores, etc.)

### Configurar IIS como Proxy Inverso (Opcional)

Si deseas usar IIS como proxy inverso para manejar HTTPS:

#### Paso 1: Instalar IIS y modulos requeridos

```powershell
# Instalar IIS
Install-WindowsFeature -Name Web-Server -IncludeManagementTools

# Instalar URL Rewrite Module
# Descargar desde: https://www.iis.net/downloads/microsoft/url-rewrite

# Instalar Application Request Routing (ARR)
# Descargar desde: https://www.iis.net/downloads/microsoft/application-request-routing
```

#### Paso 2: Habilitar proxy en ARR

1. Abrir IIS Manager
2. Seleccionar el servidor
3. Doble clic en "Application Request Routing Cache"
4. Click en "Server Proxy Settings"
5. Marcar "Enable proxy"
6. Aplicar cambios

#### Paso 3: Crear sitio web

1. En IIS Manager, click derecho en "Sites" > "Add Website"
2. Configurar:
   - Site name: Testimonios
   - Physical path: C:\inetpub\testimonios (crear carpeta vacia)
   - Binding: Puerto 80 o 443 segun corresponda

#### Paso 4: Configurar regla de reescritura

Crear archivo `C:\inetpub\testimonios\web.config`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Proxy to Docker" stopProcessing="true">
                    <match url="(.*)" />
                    <action type="Rewrite" url="http://localhost:8001/{R:1}" />
                </rule>
            </rules>
        </rewrite>
        <security>
            <requestFiltering>
                <requestLimits maxAllowedContentLength="104857600" />
            </requestFiltering>
        </security>
    </system.webServer>
</configuration>
```

#### Paso 5: Configurar certificado SSL (HTTPS)

1. Obtener certificado SSL (comprado o Let's Encrypt)
2. En IIS Manager, ir a "Server Certificates"
3. Importar el certificado
4. Editar bindings del sitio para agregar HTTPS (puerto 443) con el certificado

### Configurar Servicios de Procesamiento en Windows

#### Instalar Python

```powershell
# Usando winget
winget install Python.Python.3.11

# O descargar desde https://www.python.org/downloads/windows/
# Asegurar marcar "Add Python to PATH" durante instalacion
```

#### Instalar CUDA (para GPU)

1. Descargar CUDA Toolkit desde: https://developer.nvidia.com/cuda-downloads
2. Seleccionar Windows > x86_64 > Server 2019/2022 > exe (local)
3. Ejecutar instalador y seguir instrucciones

#### Instalar servicios de procesamiento

```powershell
cd C:\Apps\modulo-escucha-lite\services

# Crear entorno virtual
python -m venv venv
.\venv\Scripts\Activate.ps1

# Instalar dependencias NER
cd ner
pip install -r requirements.txt
python -m spacy download es_core_news_lg

# Instalar dependencias Transcripcion
cd ..\transcription
pip install -r requirements.txt
pip install git+https://github.com/m-bain/whisperx.git
```

#### Crear servicios de Windows con NSSM

Descargar NSSM (Non-Sucking Service Manager) desde: https://nssm.cc/download

```powershell
# Instalar servicio NER
nssm install NER_Service "C:\Apps\modulo-escucha-lite\services\venv\Scripts\python.exe" "C:\Apps\modulo-escucha-lite\services\ner\ner_service.py --mode api --port 5001"
nssm set NER_Service AppDirectory "C:\Apps\modulo-escucha-lite\services\ner"
nssm set NER_Service DisplayName "NER Service (spaCy)"
nssm set NER_Service Start SERVICE_AUTO_START

# Instalar servicio Transcripcion
nssm install Transcription_Service "C:\Apps\modulo-escucha-lite\services\venv\Scripts\python.exe" "C:\Apps\modulo-escucha-lite\services\transcription\transcription_service.py --mode api --port 5000"
nssm set Transcription_Service AppDirectory "C:\Apps\modulo-escucha-lite\services\transcription"
nssm set Transcription_Service DisplayName "Transcription Service (WhisperX)"
nssm set Transcription_Service Start SERVICE_AUTO_START

# Iniciar servicios
nssm start NER_Service
nssm start Transcription_Service

# Verificar estado
nssm status NER_Service
nssm status Transcription_Service
```

### Solucion de Problemas en Windows

#### Error: "Docker daemon not running"

```powershell
# Verificar servicio Docker
Get-Service Docker

# Iniciar servicio
Start-Service Docker

# Ver logs de Docker
Get-EventLog -LogName Application -Source Docker -Newest 20
```

#### Error: "Network not found" en docker-compose

```powershell
# Recrear redes de Docker
docker network prune -f
docker-compose down
docker-compose up -d
```

#### Error: Permisos en volumenes

```powershell
# Dar permisos al directorio
icacls "C:\Apps\modulo-escucha-lite\www\storage" /grant "Everyone:(OI)(CI)F" /T
icacls "C:\Apps\modulo-escucha-lite\postgres-data" /grant "Everyone:(OI)(CI)F" /T
```

#### Error: Puerto en uso

```powershell
# Verificar que proceso usa el puerto
netstat -ano | findstr :8001

# Ver detalles del proceso
Get-Process -Id <PID>

# Detener proceso si es necesario
Stop-Process -Id <PID> -Force
```

#### Contenedores no inician despues de reiniciar

```powershell
# Verificar que Docker esta corriendo
Get-Service Docker

# Iniciar contenedores manualmente
cd C:\Apps\modulo-escucha-lite
docker-compose up -d

# Verificar tarea programada
Get-ScheduledTask -TaskName "Iniciar Modulo Escucha"
```

---

## Configuracion de Servicios de Procesamiento

Los servicios de transcripcion (WhisperX) y NER (spaCy) son opcionales y corren fuera de Docker.

### Requisitos

```bash
# Python 3.9+
python --version

# CUDA (para GPU - verificar instalacion)
nvidia-smi
```

### Paso 1: Instalar dependencias del servicio NER

```bash
cd modulo-escucha-lite/services/ner
pip install -r requirements.txt

# Instalar modelo de espanol
python -m spacy download es_core_news_lg

# O usar el modelo personalizado (si existe)
# El modelo debe estar en: /ruta/a/spacyModel
```

### Paso 2: Instalar dependencias del servicio de Transcripcion

```bash
cd modulo-escucha-lite/services/transcription
pip install -r requirements.txt

# Instalar WhisperX
pip install git+https://github.com/m-bain/whisperx.git

# Para diarizacion (identificacion de hablantes)
# Requiere token de HuggingFace
pip install pyannote.audio
```

### Paso 3: Configurar URLs en Laravel

Edita `www/.env`:

```env
# Para desarrollo local (Windows con Docker)
TRANSCRIPTION_SERVICE_URL=http://host.docker.internal:5000
NER_SERVICE_URL=http://host.docker.internal:5001

# Para servidor Linux (servicios en el mismo servidor)
TRANSCRIPTION_SERVICE_URL=http://localhost:5000
NER_SERVICE_URL=http://localhost:5001

# Timeout en segundos (10 minutos por defecto)
PROCESSING_TIMEOUT=600
```

### Paso 4: Iniciar los servicios

```bash
cd modulo-escucha-lite/services

# Iniciar ambos servicios
python start_services.py

# O iniciar servicios individuales
python start_services.py --transcription  # Solo transcripcion (puerto 5000)
python start_services.py --ner            # Solo NER (puerto 5001)
```

### Paso 5: Ejecutar servicios como daemon (Produccion)

Crear archivo de servicio systemd para NER:

```bash
sudo nano /etc/systemd/system/ner-service.service
```

```ini
[Unit]
Description=NER Service (spaCy)
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/home/usuario/modulo-escucha-lite/services/ner
ExecStart=/usr/bin/python3 ner_service.py --mode api --port 5001
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Crear archivo de servicio systemd para Transcripcion:

```bash
sudo nano /etc/systemd/system/transcription-service.service
```

```ini
[Unit]
Description=Transcription Service (WhisperX)
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/home/usuario/modulo-escucha-lite/services/transcription
ExecStart=/usr/bin/python3 transcription_service.py --mode api --port 5000
Restart=always
RestartSec=10
Environment="CUDA_VISIBLE_DEVICES=0"

[Install]
WantedBy=multi-user.target
```

```bash
# Habilitar e iniciar servicios
sudo systemctl daemon-reload
sudo systemctl enable ner-service transcription-service
sudo systemctl start ner-service transcription-service

# Verificar estado
sudo systemctl status ner-service
sudo systemctl status transcription-service
```

---

## Configuracion Avanzada

### Cambiar credenciales de base de datos

1. Edita `docker-compose.yml`:
```yaml
db:
    environment:
        POSTGRES_USER: nuevo_usuario
        POSTGRES_PASSWORD: nueva_password
```

2. Edita `www/.env`:
```env
DB_USERNAME=nuevo_usuario
DB_PASSWORD=nueva_password
```

3. Reinicia los contenedores:
```bash
docker-compose down
rm -rf postgres-data  # CUIDADO: Esto borra la base de datos
docker-compose up -d
```

### Backup de base de datos

```bash
# Crear backup
docker exec mel-db pg_dump -U dba testimonios > backup_$(date +%Y%m%d).sql

# Restaurar backup
docker exec -i mel-db psql -U dba testimonios < backup_20241208.sql
```

### Aumentar limite de subida de archivos

Edita `.docker/conf/nginx/default.conf`:
```nginx
client_max_body_size 200M;
```

Edita `.docker/conf/php/php.ini`:
```ini
upload_max_filesize = 200M
post_max_size = 200M
```

Reinicia los contenedores:
```bash
docker-compose restart
```

### Logs

```bash
# Ver logs de todos los contenedores
docker-compose logs -f

# Ver logs de un contenedor especifico
docker logs -f mel-app
docker logs -f mel-web
docker logs -f mel-db

# Logs de Laravel
docker exec mel-app tail -f /var/www/storage/logs/laravel.log
```

---

## Solucion de Problemas

### Error: "Permission denied" en storage/

```bash
docker exec mel-app chmod -R 775 /var/www/storage
docker exec mel-app chmod -R 775 /var/www/bootstrap/cache
docker exec mel-app chown -R www-data:www-data /var/www/storage
```

### Error: Base de datos no inicializada

```bash
# Verificar que el contenedor de BD esta corriendo
docker ps

# Ver logs de la BD
docker logs mel-db

# Reiniciar BD (esto borra datos)
docker-compose down
rm -rf postgres-data
docker-compose up -d
```

### Error: "Class not found" despues de instalar paquetes

```bash
docker exec mel-app composer dump-autoload
docker exec mel-app php artisan config:clear
docker exec mel-app php artisan cache:clear
```

### Servicios de procesamiento no conectan

1. Verificar que los servicios estan corriendo:
```bash
curl http://localhost:5000/status
curl http://localhost:5001/status
```

2. Verificar configuracion en `.env`:
```env
# Windows con Docker Desktop
TRANSCRIPTION_SERVICE_URL=http://host.docker.internal:5000
NER_SERVICE_URL=http://host.docker.internal:5001

# Linux
TRANSCRIPTION_SERVICE_URL=http://172.17.0.1:5000
NER_SERVICE_URL=http://172.17.0.1:5001
```

3. Limpiar cache de configuracion:
```bash
docker exec mel-app php artisan config:clear
```

### Error de memoria en transcripcion

Para archivos grandes, aumentar memoria disponible o usar CPU en lugar de GPU:

```bash
# En transcription_service.py, cambiar device a "cpu"
# O reducir batch_size
```

---

## Estructura de Puertos

| Servicio | Puerto Host | Puerto Container |
|----------|-------------|------------------|
| Nginx (Web) | 8001 | 80 |
| PostgreSQL | 5556 | 5432 |
| Transcripcion | 5000 | 5000 |
| NER | 5001 | 5001 |

---

## Comandos Utiles

```bash
# Iniciar contenedores
docker-compose up -d

# Detener contenedores
docker-compose down

# Reiniciar contenedores
docker-compose restart

# Ver estado de contenedores
docker-compose ps

# Entrar al contenedor PHP
docker exec -it mel-app bash

# Ejecutar comandos Artisan
docker exec mel-app php artisan <comando>

# Ver espacio en disco de Docker
docker system df

# Limpiar recursos no usados
docker system prune -a
```

---

## Contacto y Soporte

Para reportar problemas o solicitar ayuda:
- Crear issue en el repositorio
- Contactar al equipo de desarrollo
