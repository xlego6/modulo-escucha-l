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
- Todos los contenedores levantados:
  ```
  docker ps
  ```
  Deben aparecer: `mel-web`, `mel-app`, `mel-db`, `mel-redis`, `mel-transcription`, `mel-ner`

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
git pull origin master
```

Revisar la salida para identificar qué archivos cambiaron. Esto determina los pasos siguientes.

---

### 2. Ejecutar migraciones (si hay archivos nuevos en `www/database/migrations/`)

Siempre que `git pull` muestre archivos nuevos con el patrón `www/database/migrations/*.php`:

```bash
docker exec mel-app php artisan migrate --force
```

---

### 3. Actualizar dependencias PHP (si cambió `composer.json` o `composer.lock`)

```bash
docker exec mel-app composer install --no-dev --optimize-autoloader
```

---

### 4. Reconstruir caché de Laravel

Ejecutar siempre después de un pull con cambios de código PHP o Blade:

```bash
docker exec mel-app php artisan config:cache
docker exec mel-app php artisan route:cache
docker exec mel-app php artisan view:cache
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
git pull origin master
│
├── ¿Hay archivos nuevos en www/database/migrations/?
│   └── Sí → docker exec mel-app php artisan migrate --force
│
├── ¿Cambió composer.json o composer.lock?
│   └── Sí → docker exec mel-app composer install --no-dev --optimize-autoloader
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
docker logs mel-app --tail=50
docker logs mel-web --tail=50
```

Verificar estado de migraciones:
```bash
docker exec mel-app php artisan migrate:status
```

---

## Notas sobre el servidor de producción

El mismo proceso aplica en el servidor Linux. Conectarse por SSH y ejecutar los mismos comandos desde el directorio del proyecto. La única diferencia es que en Linux el comando puede ser `docker compose` o `docker-compose` según la versión instalada.
