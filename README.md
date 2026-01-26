# Modulo Escucha Lite

Sistema ligero de gestion de testimonios basado en modulo-escucha.

## Requisitos

- Docker Engine 20.10+
- Docker Compose 2.0+
- 4 GB RAM minimo (8 GB recomendado con servicios de IA)

## Instalacion Rapida (Desarrollo)

```bash
cd modulo-escucha-lite
docker-compose up -d
docker exec -it mel-app composer install
docker exec -it mel-app php artisan key:generate
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
modulo-escucha-lite/
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

| Servicio | Puerto | Descripcion |
|----------|--------|-------------|
| mel-web  | 8001   | Nginx       |
| mel-app  | -      | PHP-FPM 8.1 |
| mel-db   | 5556   | PostgreSQL 11 |
| mel-transcription | 5000 | WhisperX (transcripcion) |
| mel-ner  | 5001   | spaCy (NER) |
| mel-redis | 6379  | Redis (colas) |

## Base de Datos

- Host: mel-db (interno) / localhost:5556 (externo)
- Database: testimonios
- Usuario: dba

### Esquemas

- `esclarecimiento` - Entrevistas, entrevistadores
- `fichas` - Personas, consentimientos
- `catalogos` - Catalogos, geografia

## Comandos Utiles

```bash
# Ver logs
docker-compose logs -f

# Entrar al contenedor PHP
docker exec -it mel-app bash

# Ejecutar artisan
docker exec -it mel-app php artisan [comando]

# Reiniciar BD (elimina datos)
docker-compose down -v
rm -rf postgres-data
docker-compose up -d
```

## Desarrollo

Para adaptar funcionalidades del modulo original:

1. Revisar codigo en `../modulo-de-captura/` o `../www/`
2. Copiar y simplificar controladores/modelos necesarios
3. Adaptar vistas Blade

## Modulos Implementados

- [x] Autenticacion local
- [x] CRUD Entrevistas (con wizard)
- [x] CRUD Personas
- [x] Gestion de Adjuntos
- [x] Buscador avanzado
- [x] Estadisticas y Dashboard
- [x] Mapa geografico
- [x] Exportacion Excel
- [x] Gestion de Usuarios
- [x] Permisos y control de acceso
- [x] Catalogos
- [x] Traza de actividad
- [x] Transcripcion automatica (WhisperX)
- [x] Deteccion de entidades (spaCy NER)
- [x] Anonimizacion de textos
