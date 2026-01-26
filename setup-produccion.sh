#!/bin/bash
#
# Script de Configuracion para Produccion - Modulo Escucha Lite
# Este script prepara el sistema para despliegue en servidor
#

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Directorio del script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Configuracion de Produccion - Modulo Escucha  ${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Funcion para generar password segura
generate_password() {
    openssl rand -base64 32 | tr -d '/+=' | head -c 24
}

# Funcion para validar URL
validate_url() {
    if [[ $1 =~ ^https?:// ]]; then
        return 0
    else
        return 1
    fi
}

# ============================================
# PASO 1: Recopilar informacion
# ============================================
echo -e "${YELLOW}[1/8] Configuracion inicial${NC}"
echo ""

# URL de la aplicacion
read -p "URL de la aplicacion (ej: https://testimonios.ejemplo.com): " APP_URL
while ! validate_url "$APP_URL"; do
    echo -e "${RED}URL invalida. Debe comenzar con http:// o https://${NC}"
    read -p "URL de la aplicacion: " APP_URL
done

# Password de base de datos
echo ""
echo "Password para PostgreSQL:"
echo "  1) Generar automaticamente (recomendado)"
echo "  2) Ingresar manualmente"
read -p "Opcion [1]: " DB_PASS_OPTION
DB_PASS_OPTION=${DB_PASS_OPTION:-1}

if [ "$DB_PASS_OPTION" == "1" ]; then
    DB_PASSWORD=$(generate_password)
    echo -e "${GREEN}Password generada: ${DB_PASSWORD}${NC}"
    echo -e "${YELLOW}IMPORTANTE: Guarda esta password en un lugar seguro${NC}"
else
    read -sp "Ingresa la password de PostgreSQL: " DB_PASSWORD
    echo ""
    read -sp "Confirma la password: " DB_PASSWORD_CONFIRM
    echo ""
    if [ "$DB_PASSWORD" != "$DB_PASSWORD_CONFIRM" ]; then
        echo -e "${RED}Las passwords no coinciden. Abortando.${NC}"
        exit 1
    fi
fi

# HuggingFace Token
echo ""
echo "Token de HuggingFace (para diarizacion de audio):"
echo "  - Dejar vacio para deshabilitar diarizacion"
echo "  - Obtener token en: https://huggingface.co/settings/tokens"
read -p "HF_TOKEN [dejar vacio para omitir]: " HF_TOKEN

# ============================================
# PASO 2: Backup de archivos originales
# ============================================
echo ""
echo -e "${YELLOW}[2/8] Creando backup de configuracion actual${NC}"

BACKUP_DIR="backups/config_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

cp docker-compose.yml "$BACKUP_DIR/" 2>/dev/null || true
cp www/.env "$BACKUP_DIR/www.env" 2>/dev/null || true
cp www/.env.example "$BACKUP_DIR/www.env.example" 2>/dev/null || true

echo -e "${GREEN}Backup creado en: $BACKUP_DIR${NC}"

# ============================================
# PASO 3: Configurar docker-compose.yml
# ============================================
echo ""
echo -e "${YELLOW}[3/8] Configurando docker-compose.yml${NC}"

# Cambiar password de PostgreSQL
sed -i "s/POSTGRES_PASSWORD: sql/POSTGRES_PASSWORD: ${DB_PASSWORD}/" docker-compose.yml
sed -i "s/POSTGRES_PASSWORD: .*/POSTGRES_PASSWORD: ${DB_PASSWORD}/" docker-compose.yml

# Configurar HF_TOKEN
if [ -n "$HF_TOKEN" ]; then
    sed -i "s/- HF_TOKEN=.*/- HF_TOKEN=${HF_TOKEN}/" docker-compose.yml
else
    sed -i "s/- HF_TOKEN=.*$/- HF_TOKEN=/" docker-compose.yml
fi

# Comentar puertos expuestos innecesariamente (excepto web)
# Solo mantener puerto 8001 para web, comentar 5556, 5000, 5001
sed -i 's/^\(\s*\)- 5556:5432/\1# - 5556:5432  # Comentado por seguridad/' docker-compose.yml
sed -i 's/^\(\s*\)- 5000:5000/\1# - 5000:5000  # Comentado por seguridad/' docker-compose.yml
sed -i 's/^\(\s*\)- 5001:5001/\1# - 5001:5001  # Comentado por seguridad/' docker-compose.yml
sed -i 's/^\(\s*\)- 6379:6379/\1# - 6379:6379  # Comentado por seguridad/' docker-compose.yml

echo -e "${GREEN}docker-compose.yml configurado${NC}"

# ============================================
# PASO 4: Configurar www/.env
# ============================================
echo ""
echo -e "${YELLOW}[4/8] Configurando www/.env${NC}"

# Crear .env desde .env.example si no existe
if [ ! -f www/.env ]; then
    cp www/.env.example www/.env
fi

# Configurar variables de produccion
sed -i "s|APP_ENV=.*|APP_ENV=production|" www/.env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" www/.env
sed -i "s|APP_URL=.*|APP_URL=${APP_URL}|" www/.env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" www/.env

# Configurar URLs de servicios internos (dentro de Docker network)
sed -i "s|TRANSCRIPTION_SERVICE_URL=.*|TRANSCRIPTION_SERVICE_URL=http://transcription:5000|" www/.env
sed -i "s|NER_SERVICE_URL=.*|NER_SERVICE_URL=http://ner:5001|" www/.env

echo -e "${GREEN}www/.env configurado${NC}"

# ============================================
# PASO 5: Iniciar contenedores
# ============================================
echo ""
echo -e "${YELLOW}[5/8] Iniciando contenedores Docker${NC}"

# Detener contenedores existentes
docker-compose down 2>/dev/null || true

# Eliminar datos de BD anteriores si es instalacion limpia
if [ -d "postgres-data" ]; then
    read -p "Se encontro una base de datos existente. Eliminar y crear nueva? [s/N]: " DELETE_DB
    if [[ "$DELETE_DB" =~ ^[Ss]$ ]]; then
        rm -rf postgres-data
        echo -e "${YELLOW}Base de datos eliminada. Se creara una nueva.${NC}"
    fi
fi

# Construir e iniciar
docker-compose up -d --build

echo -e "${GREEN}Contenedores iniciados${NC}"

# Esperar a que la BD este lista
echo "Esperando a que PostgreSQL este listo..."
sleep 10

# ============================================
# PASO 6: Instalar dependencias y generar key
# ============================================
echo ""
echo -e "${YELLOW}[6/8] Instalando dependencias PHP${NC}"

docker exec mel-app composer install --no-dev --optimize-autoloader

echo ""
echo -e "${YELLOW}[6/8] Generando APP_KEY${NC}"

docker exec mel-app php artisan key:generate --force

echo -e "${GREEN}APP_KEY generada${NC}"

# ============================================
# PASO 7: Optimizar para produccion
# ============================================
echo ""
echo -e "${YELLOW}[7/8] Optimizando para produccion${NC}"

docker exec mel-app php artisan config:cache
docker exec mel-app php artisan route:cache
docker exec mel-app php artisan view:cache

# Configurar permisos de storage
docker exec mel-app chmod -R 775 /var/www/storage
docker exec mel-app chmod -R 775 /var/www/bootstrap/cache
docker exec mel-app chown -R www-data:www-data /var/www/storage
docker exec mel-app chown -R www-data:www-data /var/www/bootstrap/cache

echo -e "${GREEN}Optimizacion completada${NC}"

# ============================================
# PASO 8: Verificacion final
# ============================================
echo ""
echo -e "${YELLOW}[8/8] Verificacion final${NC}"

# Verificar contenedores
echo ""
echo "Estado de contenedores:"
docker-compose ps

# Verificar conexion a BD
echo ""
echo "Verificando conexion a base de datos..."
if docker exec mel-app php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null | grep -q "OK"; then
    echo -e "${GREEN}Conexion a BD: OK${NC}"
else
    echo -e "${RED}Conexion a BD: FALLO${NC}"
fi

# ============================================
# RESUMEN FINAL
# ============================================
echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}          CONFIGURACION COMPLETADA              ${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""
echo -e "${GREEN}La aplicacion esta lista para produccion.${NC}"
echo ""
echo "Credenciales de base de datos:"
echo "  Usuario: dba"
echo "  Password: ${DB_PASSWORD}"
echo ""
echo "Acceso a la aplicacion:"
echo "  URL: ${APP_URL}"
echo "  Puerto Docker: 8001"
echo ""
echo "Usuario administrador por defecto:"
echo "  Email: admin@example.com"
echo "  Password: password"
echo ""
echo -e "${RED}IMPORTANTE - Acciones pendientes:${NC}"
echo "  1. Cambiar password del usuario admin inmediatamente"
echo "  2. Configurar proxy inverso (Nginx/Apache) con HTTPS"
echo "  3. Guardar las credenciales en un lugar seguro"
echo ""
echo "Archivo de backup: $BACKUP_DIR"
echo ""

# Guardar credenciales en archivo seguro
CREDS_FILE="$BACKUP_DIR/credenciales.txt"
cat > "$CREDS_FILE" << EOF
===========================================
CREDENCIALES - Modulo Escucha Lite
Generadas: $(date)
===========================================

Base de Datos PostgreSQL:
  Host: mel-db (interno) / localhost:5556 (si puerto habilitado)
  Database: testimonios
  Usuario: dba
  Password: ${DB_PASSWORD}

Usuario Administrador:
  Email: admin@example.com
  Password: password (CAMBIAR INMEDIATAMENTE)

URL Aplicacion: ${APP_URL}

HuggingFace Token: ${HF_TOKEN:-No configurado}
===========================================
EOF

chmod 600 "$CREDS_FILE"
echo -e "${YELLOW}Credenciales guardadas en: $CREDS_FILE${NC}"
echo -e "${YELLOW}Protege este archivo adecuadamente.${NC}"
