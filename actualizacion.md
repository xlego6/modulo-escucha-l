# Guía de Actualización — Módulo Escucha Lite

Este documento describe el proceso completo para actualizar la aplicación a partir de cambios disponibles en GitHub.

---

## Contexto

El flujo de trabajo habitual es:
1. Se desarrollan cambios en una máquina de desarrollo
2. Los cambios se suben a GitHub (`git push`)
3. En el equipo local o servidor se actualiza la aplicación con este proceso

---

## Requisitos previos

- Docker Desktop corriendo (Windows) o Docker Engine activo (Linux)
- Todos los contenedores levantados (verificar con `docker ps`): web, php, db, redis, transcription y ner.

  El **nombre real de los contenedores varía según el entorno**; confirmarlo siempre con `docker ps`:

  | Entorno | Contenedor PHP (ejemplo) |
  |---------|--------------------------|
  | Desarrollo local | `modulo-escucha-l-php-1` |
  | Servidores (pruebas/producción) | `escucha-php-1` |

  En los comandos de esta guía, reemplazar `<php>` por el nombre que muestre `docker ps`.

  Si no están corriendo, levantarlos con:
  ```bash
  cd /ruta/al/proyecto
  docker compose up -d
  ```

---

## Proceso de actualización

### 1. Bajar los últimos cambios de GitHub

```bash
cd /ruta/al/proyecto
git pull origin moduloii
```

> La rama de despliegue es **`moduloii`** (la principal del repo es `alejodev`).

Revisar la salida para identificar qué archivos cambiaron. Esto determina los pasos siguientes.

> **Importante (bind mount):** en algunos servidores el bind mount de `./www` no refleja
> los cambios dentro del contenedor tras el pull. Verificar (por ejemplo, comparando un
> archivo recién cambiado) y, si no sincronizó, nivelar a mano:
> ```bash
> docker cp www/ruta/al/Archivo.php <php>:/var/www/ruta/al/Archivo.php
> ```

---

### 2. Ejecutar migraciones (si hay archivos nuevos en `www/database/migrations/`)

Siempre que `git pull` muestre archivos nuevos con el patrón `www/database/migrations/*.php`:

```bash
docker exec <php> php artisan migrate --force
```

Si la migración tocó la estructura de la BD, regenerar el diccionario de datos del servidor:

```bash
docker exec <php> php artisan dic:generar
```

---

### 3. Actualizar dependencias PHP (si cambió `composer.json` o `composer.lock`)

```bash
docker exec <php> composer install --no-dev --optimize-autoloader
```

---

### 4. Reconstruir caché de Laravel

Ejecutar siempre después de un pull con cambios de código PHP o Blade:

```bash
docker exec <php> php artisan config:cache
docker exec <php> php artisan route:cache
docker exec <php> php artisan view:cache
```

---

### 5. Reconstruir contenedores (solo si cambiaron archivos de infraestructura)

Aplica cuando `git pull` muestre cambios en alguno de estos archivos:
- `.docker/conf/nginx/default.conf`
- `.docker/conf/php/php.ini`
- `.docker/Dockerfile`
- `services/transcription/Dockerfile` o `transcription_service.py`
- `services/ner/Dockerfile`
- `docker-compose.yml`

**a) Si solo cambió la config de nginx o PHP.ini** (no el Dockerfile):

```bash
docker compose restart web php
```

**b) Si cambió el código de un servicio Python** (transcripción, NER) o su Dockerfile:

```bash
docker compose build transcription   # o 'ner' según corresponda
docker compose up -d --no-deps transcription
```

**c) Si cambió `docker-compose.yml`**:

```bash
docker compose up -d
```

---

## Resumen: árbol de decisión

```
git pull origin moduloii
│
├── ¿El bind mount de ./www sincronizó dentro del contenedor?
│   └── No → docker cp de los archivos cambiados
│
├── ¿Hay archivos nuevos en www/database/migrations/?
│   └── Sí → docker exec <php> php artisan migrate --force
│            (y si cambió la estructura: docker exec <php> php artisan dic:generar)
│
├── ¿Cambió composer.json o composer.lock?
│   └── Sí → docker exec <php> composer install --no-dev --optimize-autoloader
│
├── ¿Cambiaron archivos PHP o Blade?
│   └── Sí → reconstruir caché (config:cache, route:cache, view:cache)
│
└── ¿Cambiaron archivos de infraestructura (nginx, php.ini, Dockerfiles)?
    ├── Solo config → docker compose restart web php
    └── Dockerfile o código Python → docker compose build <servicio> && docker compose up -d
```

---

## Comandos de diagnóstico

Verificar que los contenedores estén saludables:
```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

Ver logs de un contenedor:
```bash
docker logs <php> --tail=50
docker logs <web> --tail=50
```

Verificar estado de migraciones:
```bash
docker exec <php> php artisan migrate:status
```

---

## Notas sobre el servidor de producción

El mismo proceso aplica en el servidor Linux. Conectarse por SSH y ejecutar los mismos comandos desde el directorio del proyecto. La única diferencia es que en Linux el comando puede ser `docker compose` o `docker-compose` según la versión instalada.
