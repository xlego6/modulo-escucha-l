-- Migración: Simplificar niveles de acceso
-- Fecha: 2024-12-23
-- Nuevos niveles: Administrador, Líder, Entrevistador, Transcriptor, Deshabilitado

-- Paso 1: Actualizar usuarios con niveles que se eliminarán
-- Supervisor (3), Coordinador (4) -> Líder (2)
UPDATE esclarecimiento.entrevistador SET id_nivel = 2 WHERE id_nivel IN (3, 4);

-- Entrevistador antiguo (5) -> Entrevistador nuevo (3)
UPDATE esclarecimiento.entrevistador SET id_nivel = 3 WHERE id_nivel = 5;

-- Confidencial (6), Estadísticas (7) -> Entrevistador (3)
UPDATE esclarecimiento.entrevistador SET id_nivel = 3 WHERE id_nivel IN (6, 7);

-- Transcriptor antiguo (10) -> Transcriptor nuevo (4)
UPDATE esclarecimiento.entrevistador SET id_nivel = 4 WHERE id_nivel = 10;

-- Etiquetador (11) -> Transcriptor (4)
UPDATE esclarecimiento.entrevistador SET id_nivel = 4 WHERE id_nivel = 11;

-- Paso 2: Eliminar niveles obsoletos
DELETE FROM catalogos.criterio_fijo WHERE id_grupo = 1 AND id_opcion IN (3, 4, 5, 6, 7, 10, 11);

-- Paso 3: Renombrar y reordenar niveles restantes
UPDATE catalogos.criterio_fijo SET descripcion = 'Líder', abreviado = 'Líder', orden = 2 WHERE id_opcion = 2 AND id_grupo = 1;

-- Paso 4: Insertar nuevos niveles con los IDs correctos (si no existen)
INSERT INTO catalogos.criterio_fijo (id_opcion, id_grupo, descripcion, abreviado, orden)
SELECT 3, 1, 'Entrevistador', 'Ent', 3
WHERE NOT EXISTS (SELECT 1 FROM catalogos.criterio_fijo WHERE id_opcion = 3 AND id_grupo = 1);

INSERT INTO catalogos.criterio_fijo (id_opcion, id_grupo, descripcion, abreviado, orden)
SELECT 4, 1, 'Transcriptor', 'Trans', 4
WHERE NOT EXISTS (SELECT 1 FROM catalogos.criterio_fijo WHERE id_opcion = 4 AND id_grupo = 1);

-- Verificar resultado
SELECT id_opcion, descripcion, abreviado, orden
FROM catalogos.criterio_fijo
WHERE id_grupo = 1
ORDER BY orden;
