-- =============================================
-- MIGRACION: Corregir catalogo de Formato del Testimonio
-- Este script actualiza el catalogo id_cat=6 para que contenga
-- los formatos correctos: Audio, Audiovisual, Escrito, Otra indole
-- =============================================

-- Primero, eliminar los items actuales del catalogo 6 (formatos)
DELETE FROM catalogos.cat_item WHERE id_cat = 6;

-- Actualizar la descripcion del catalogo si es necesario
UPDATE catalogos.cat_cat
SET nombre = 'Formato del Testimonio',
    descripcion = 'Formato en que fueron producidos los documentos'
WHERE id_cat = 6;

-- Insertar los formatos correctos
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, habilitado) VALUES
(60, 6, 'Audio', 'AUD', 1, 1),
(61, 6, 'Audiovisual', 'AV', 2, 1),
(62, 6, 'Escrito', 'ESC', 3, 1),
(63, 6, 'Otra indole', 'OTR', 4, 1);

-- Si los IDs ya existen, usar UPDATE en lugar de INSERT
-- Esta es una alternativa mas segura usando ON CONFLICT
-- INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, habilitado) VALUES
-- (60, 6, 'Audio', 'AUD', 1, 1),
-- (61, 6, 'Audiovisual', 'AV', 2, 1),
-- (62, 6, 'Escrito', 'ESC', 3, 1),
-- (63, 6, 'Otra indole', 'OTR', 4, 1)
-- ON CONFLICT (id_item) DO UPDATE SET descripcion = EXCLUDED.descripcion, abreviado = EXCLUDED.abreviado;

-- Resetear secuencia si es necesario
SELECT setval('catalogos.cat_item_id_item_seq', (SELECT MAX(id_item) FROM catalogos.cat_item));
