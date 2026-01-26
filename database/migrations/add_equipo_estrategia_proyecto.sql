-- =============================================
-- Migración: Agregar Equipo/Estrategia y Nombre Proyecto
-- Cambiar lógica de código según Dependencia de Origen
-- =============================================

-- 1. Agregar campos nuevos a entrevista
ALTER TABLE esclarecimiento.e_ind_fvt
ADD COLUMN IF NOT EXISTS id_equipo_estrategia INTEGER,
ADD COLUMN IF NOT EXISTS nombre_proyecto VARCHAR(500);

COMMENT ON COLUMN esclarecimiento.e_ind_fvt.id_equipo_estrategia IS 'Equipo/Estrategia dependiente de Dependencia de Origen';
COMMENT ON COLUMN esclarecimiento.e_ind_fvt.nombre_proyecto IS 'Nombre del proyecto/investigación/caso';

-- 2. Crear catálogo de Equipo/Estrategia (id_cat = 18)
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable)
VALUES (18, 'Equipo/Estrategia', 'Equipos o estrategias por dependencia de origen', 1)
ON CONFLICT (id_cat) DO NOTHING;

-- 3. Items de Equipo/Estrategia por Dependencia
-- DMMC (id_item 30)
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, otro)
VALUES
(180, 18, 'Dimensión Física', 'DF', 1, '30'),
(181, 18, 'Dimensión Territorial', 'DT', 2, '30'),
(182, 18, 'Dimensión Virtual', 'DV', 3, '30')
ON CONFLICT (id_item) DO NOTHING;

-- DCMH (id_item 31)
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, otro)
VALUES
(183, 18, 'Investigación para el Esclarecimiento', 'IE', 4, '31'),
(184, 18, 'Iniciativas de Memoria Histórica', 'IMH', 5, '31'),
(185, 18, 'Reparaciones', 'REP', 6, '31')
ON CONFLICT (id_item) DO NOTHING;

-- DADH (id_item 33)
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, otro)
VALUES
(186, 18, 'Testimonios', 'TES', 7, '33'),
(187, 18, 'Fondos documentales', 'FD', 8, '33')
ON CONFLICT (id_item) DO NOTHING;

-- DAV (id_item 32)
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, otro)
VALUES
(188, 18, 'Esclarecimiento del fenómeno paramilitar', 'EFP', 9, '32'),
(189, 18, 'Contribuciones Voluntarias', 'CV', 10, '32')
ON CONFLICT (id_item) DO NOTHING;

-- Estrategias - copian mismo nombre (id_items 34-40)
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, otro)
VALUES
(190, 18, 'Estrategia de Comunicaciones', 'EC', 11, '34'),
(191, 18, 'Dirección General', 'DG', 12, '35'),
(192, 18, 'Estrategia de Pedagogía', 'EP', 13, '36'),
(193, 18, 'Estrategia de Enfoques Diferenciales', 'EED', 14, '37'),
(194, 18, 'Estrategia Psicosocial', 'EPS', 15, '38'),
(195, 18, 'Estrategia de Territorialización', 'ET', 16, '39'),
(196, 18, 'Testimonio allegado al CNMH', 'TA', 17, '40')
ON CONFLICT (id_item) DO NOTHING;

-- 4. Actualizar abreviados de Dependencia de Origen para códigos
-- Según especificación: DMMC, DCMH, DAV, DADH, y TRA para estrategias
UPDATE catalogos.cat_item SET abreviado = 'DMMC' WHERE id_item = 30;
UPDATE catalogos.cat_item SET abreviado = 'DCMH' WHERE id_item = 31;
UPDATE catalogos.cat_item SET abreviado = 'DAV' WHERE id_item = 32;
UPDATE catalogos.cat_item SET abreviado = 'DADH' WHERE id_item = 33;
UPDATE catalogos.cat_item SET abreviado = 'TRA' WHERE id_item = 34; -- Estrategia de Comunicaciones
UPDATE catalogos.cat_item SET abreviado = 'TRA' WHERE id_item = 35; -- Dirección General
UPDATE catalogos.cat_item SET abreviado = 'TRA' WHERE id_item = 36; -- Estrategia de Pedagogía
UPDATE catalogos.cat_item SET abreviado = 'TRA' WHERE id_item = 37; -- Estrategia de Enfoques Diferenciales
UPDATE catalogos.cat_item SET abreviado = 'TRA' WHERE id_item = 38; -- Estrategia Psicosocial
UPDATE catalogos.cat_item SET abreviado = 'TRA' WHERE id_item = 39; -- Estrategia de Territorialización
UPDATE catalogos.cat_item SET abreviado = 'TRA' WHERE id_item = 40; -- Testimonio allegado

-- 5. Crear índice para nuevo campo
CREATE INDEX IF NOT EXISTS idx_entrevista_equipo ON esclarecimiento.e_ind_fvt(id_equipo_estrategia);

-- 6. Resetear secuencia de cat_item
SELECT setval('catalogos.cat_item_id_item_seq', (SELECT MAX(id_item) FROM catalogos.cat_item));
