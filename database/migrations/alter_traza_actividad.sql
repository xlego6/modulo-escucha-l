-- Migración para actualizar la tabla traza_actividad
-- Ejecutar en la base de datos existente

-- Eliminar columnas antiguas si existen
ALTER TABLE traza_actividad DROP COLUMN IF EXISTS id_accion;
ALTER TABLE traza_actividad DROP COLUMN IF EXISTS id_objeto;
ALTER TABLE traza_actividad DROP COLUMN IF EXISTS id_primaria;

-- Agregar nuevas columnas
ALTER TABLE traza_actividad ADD COLUMN IF NOT EXISTS accion VARCHAR(100);
ALTER TABLE traza_actividad ADD COLUMN IF NOT EXISTS objeto VARCHAR(100);
ALTER TABLE traza_actividad ADD COLUMN IF NOT EXISTS id_registro INTEGER;
ALTER TABLE traza_actividad ADD COLUMN IF NOT EXISTS ip VARCHAR(45);

-- Ampliar referencia si es necesario
ALTER TABLE traza_actividad ALTER COLUMN referencia TYPE VARCHAR(500);

-- Crear índices para mejorar búsquedas
CREATE INDEX IF NOT EXISTS idx_traza_fecha ON traza_actividad(fecha_hora);
CREATE INDEX IF NOT EXISTS idx_traza_usuario ON traza_actividad(id_usuario);
CREATE INDEX IF NOT EXISTS idx_traza_accion ON traza_actividad(accion);
