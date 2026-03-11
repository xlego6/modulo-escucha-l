# Modulo Escucha Lite

Sistema ligero de gestion de testimonios basado en modulo-escucha.

## Requisitos

- Docker Engine 20.10+
- Docker Compose 2.0+
- 4 GB RAM minimo (8 GB recomendado con servicios de IA)

## Instalacion Rapida (Desarrollo)

### Windows (CMD o PowerShell)

```cmd
cd modulo-escucha-l

:: Copiar configuracion
copy www\.env.example www\.env

:: Iniciar contenedores
docker compose up -d

:: Instalar dependencias PHP
docker compose exec php composer install

:: Crear directorios de storage y permisos
docker compose exec php mkdir -p /var/www/storage/logs /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/framework/views
docker compose exec php chmod -R 775 /var/www/storage /var/www/bootstrap/cache
docker compose exec php chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

:: Generar clave
docker compose exec -w //var/www php php artisan key:generate

:: Ejecutar migraciones
docker compose exec -w //var/www php php artisan migrate --force
```

### Linux / Mac

```bash
cd modulo-escucha-l

# Copiar configuracion
cp www/.env.example www/.env

# Iniciar contenedores
docker compose up -d

# Instalar dependencias PHP
docker compose exec php composer install

# Crear directorios de storage y permisos
docker compose exec php mkdir -p /var/www/storage/logs /var/www/storage/framework/{cache,sessions,views}
docker compose exec php chmod -R 775 /var/www/storage /var/www/bootstrap/cache
docker compose exec php chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Generar clave
docker compose exec -w /var/www php php artisan key:generate

# Ejecutar migraciones
docker compose exec -w /var/www php php artisan migrate --force
```

Acceder a: http://localhost:8001
- Usuario: admin@example.com
- Password: password

## Instalacion en Produccion

Usar los scripts automatizados que configuran seguridad, passwords y optimizaciones:

**Linux/Mac:**
```bash
chmod +x setup-produccion.sh
./setup-produccion.sh
```

**Windows (PowerShell como Administrador):**
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope Process
.\setup-produccion.ps1
```

Los scripts realizan automaticamente:
- Generar password segura para PostgreSQL
- Configurar APP_ENV=production y APP_DEBUG=false
- Generar APP_KEY
- Comentar puertos internos expuestos (seguridad)
- Instalar dependencias optimizadas
- Cachear configuracion, rutas y vistas
- Configurar permisos de storage
- Crear backup de configuracion anterior

Para instalacion manual detallada, ver [instalacion.md](instalacion.md)

## Estructura

```
modulo-escucha-l/
├── docker-compose.yml      # Configuracion Docker
├── .docker/                # Dockerfile y configs
├── database/
│   └── init.sql           # Script SQL inicial
└── www/                   # Aplicacion Laravel
    ├── app/
    │   ├── Http/Controllers/
    │   └── Models/        # Modelos simplificados
    ├── config/
    ├── routes/
    └── resources/views/
```

## Servicios Docker

| Nombre del contenedor               | Puerto externo | Descripcion               |
|-------------------------------------|----------------|---------------------------|
| modulo-escucha-l-web-1              | 8001           | Nginx                     |
| modulo-escucha-l-php-1              | -              | PHP-FPM 8.1               |
| modulo-escucha-l-db-1               | 5556           | PostgreSQL 11             |
| modulo-escucha-l-transcription-1    | 8091           | WhisperX (transcripcion)  |
| modulo-escucha-l-ner-1              | 8092           | spaCy (NER)               |
| modulo-escucha-l-redis-1            | 6380           | Redis (colas)             |

## Base de Datos

- Host: `modulo-escucha-l-db-1` (interno) / `localhost:5556` (externo)
- Database: testimonios
- Usuario: dba

### Esquemas

- `esclarecimiento` — Entrevistas, entrevistadores, adjuntos, asignaciones
- `fichas` — Personas, consentimientos
- `catalogos` — Catalogos, geografia

## Comandos Utiles

> En Windows usar `//var/www` en lugar de `/var/www` para la opcion `-w`.

```bash
# Ver logs de todos los servicios
docker compose logs -f

# Ver logs de un servicio especifico
docker compose logs -f php
docker compose logs -f web

# Entrar al contenedor PHP
docker compose exec php bash

# Ejecutar artisan (Linux/Mac)
docker compose exec -w /var/www php php artisan

# Ejecutar artisan (Windows)
docker compose exec -w //var/www php php artisan

# Limpiar cache de vistas y configuracion
docker compose exec -w //var/www php php artisan view:clear
docker compose exec -w //var/www php php artisan config:clear

# Permisos de storage (si hay errores de escritura)
docker compose exec php chmod -R 775 /var/www/storage /var/www/bootstrap/cache
docker compose exec php chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
```

## Adjuntos de Audio/Video — Duracion

La duracion de los archivos de audio y video se extrae automaticamente con ffprobe al subir cada adjunto.

Al desplegar en un servidor nuevo o migrar datos existentes, ejecutar una vez para poblar las duraciones:

```bash
docker compose exec -w /var/www php php artisan adjuntos:actualizar-duracion --todos
```

Si hay adjuntos subidos anteriormente que muestran `N/A` o `0:00:00` en su duracion, ejecutar:

```bash
# Actualizar adjuntos sin duracion (hasta 200 a la vez)
docker compose exec php sh -c "cd /var/www && php artisan adjuntos:actualizar-duracion"

# Si quedan pendientes, repetir o aumentar el limite
docker compose exec php sh -c "cd /var/www && php artisan adjuntos:actualizar-duracion --limite=1000"

# Forzar reprocesar todos los audio/video (incluso los que ya tienen duracion)
docker compose exec php sh -c "cd /var/www && php artisan adjuntos:actualizar-duracion --todos"

# Actualizar un adjunto especifico por ID
docker compose exec php sh -c "cd /var/www && php artisan adjuntos:actualizar-duracion --id=42"
```

> ffprobe viene incluido en la imagen del contenedor `php`. Si despues de una reconstruccion los adjuntos vuelven a mostrar N/A, verificar que la imagen incluya ffmpeg: `docker compose exec php sh -c "which ffprobe"`. Si no aparece, reconstruir: `docker compose build --no-cache php && docker compose up -d php`.

## Reconstruir servicios de IA

```bash
# Reconstruir modelo NER (si no detecta entidades correctamente)
docker compose build --no-cache ner && docker compose up -d ner

# Actualizar servicio de transcripcion (nueva version de WhisperX)
docker compose build --no-cache transcription && docker compose up -d transcription

# Reconstruir contenedor PHP (si faltan extensiones o ffmpeg)
docker compose build --no-cache php && docker compose up -d php
```

## Reiniciar base de datos (elimina todos los datos)

```bash
docker compose down -v
rm -rf postgres-data
docker compose up -d
```
