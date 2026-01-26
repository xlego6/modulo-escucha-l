-- =============================================
-- Migración: Extender tabla permiso para desclasificación
-- Fecha: 2024
-- =============================================

-- Agregar nuevos campos a la tabla permiso
ALTER TABLE esclarecimiento.permiso
ADD COLUMN IF NOT EXISTS fecha_desde DATE,
ADD COLUMN IF NOT EXISTS fecha_hasta DATE,
ADD COLUMN IF NOT EXISTS id_adjunto INTEGER REFERENCES esclarecimiento.adjunto(id_adjunto),
ADD COLUMN IF NOT EXISTS id_estado INTEGER DEFAULT 1,
ADD COLUMN IF NOT EXISTS id_revocado_por INTEGER REFERENCES esclarecimiento.entrevistador(id_entrevistador),
ADD COLUMN IF NOT EXISTS fecha_revocado TIMESTAMP,
ADD COLUMN IF NOT EXISTS codigo_entrevista VARCHAR(50);

-- Comentarios de los campos
COMMENT ON COLUMN esclarecimiento.permiso.fecha_desde IS 'Fecha desde la cual aplica el permiso (para desclasificación)';
COMMENT ON COLUMN esclarecimiento.permiso.fecha_hasta IS 'Fecha hasta la cual aplica el permiso (para desclasificación)';
COMMENT ON COLUMN esclarecimiento.permiso.id_adjunto IS 'Documento de soporte de la autorización';
COMMENT ON COLUMN esclarecimiento.permiso.id_estado IS '1=Vigente, 2=Revocado';
COMMENT ON COLUMN esclarecimiento.permiso.id_revocado_por IS 'Usuario que revocó el permiso';
COMMENT ON COLUMN esclarecimiento.permiso.fecha_revocado IS 'Fecha en que se revocó el permiso';
COMMENT ON COLUMN esclarecimiento.permiso.codigo_entrevista IS 'Código de la entrevista (cache para búsquedas)';

-- Índices para mejorar búsquedas
CREATE INDEX IF NOT EXISTS idx_permiso_estado ON esclarecimiento.permiso(id_estado);
CREATE INDEX IF NOT EXISTS idx_permiso_fecha_desde ON esclarecimiento.permiso(fecha_desde);
CREATE INDEX IF NOT EXISTS idx_permiso_fecha_hasta ON esclarecimiento.permiso(fecha_hasta);
CREATE INDEX IF NOT EXISTS idx_permiso_codigo ON esclarecimiento.permiso(codigo_entrevista);

-- Agregar criterios fijos para estados de permiso (grupo 30)
INSERT INTO catalogos.criterio_fijo (id_opcion, id_grupo, descripcion, abreviado, orden, habilitado)
VALUES
(301, 30, 'Vigente', 'VIG', 1, 1),
(302, 30, 'Revocado', 'REV', 2, 1)
ON CONFLICT (id_opcion) DO NOTHING;

-- Actualizar permisos existentes con estado vigente
UPDATE esclarecimiento.permiso SET id_estado = 1 WHERE id_estado IS NULL;
