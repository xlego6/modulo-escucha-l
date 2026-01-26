-- Migración: Actualizar Equipo/Estrategia con nuevas opciones según Dependencia de Origen
-- El campo 'otro' almacena el id_item de la dependencia padre o 'otros' para estrategias generales
--
-- REGLAS:
-- DMMC (30): Dimensión Física, Dimensión Territorial, Dimensión Virtual
-- DCMH (31): Investigación para el Esclarecimiento, Iniciativas de Memoria Histórica, Reparaciones
-- DAV (32): Esclarecimiento del fenómeno paramilitar, Contribuciones Voluntarias
-- DADH (33): Testimonios, Fondos documentales
-- Otros (34-40): Estrategia de Comunicaciones, Dirección General, Estrategia de Pedagogía,
--                Estrategia de Enfoques Diferenciales, Estrategia Psicosocial,
--                Estrategia de Territorialización, Testimonio allegado al CNMH

-- Deshabilitar todos los items antiguos de Equipo/Estrategia
UPDATE catalogos.cat_item SET habilitado = 0 WHERE id_cat = 18;

-- Insertar nuevos items (si no existen por descripción)
-- Para DMMC (30)
INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Dimensión Física', 'DF', 1, '30', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Dimensión Física' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Dimensión Territorial', 'DT', 2, '30', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Dimensión Territorial' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Dimensión Virtual', 'DV', 3, '30', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Dimensión Virtual' AND habilitado = 1);

-- Para DCMH (31)
INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Investigación para el Esclarecimiento', 'IPE', 10, '31', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Investigación para el Esclarecimiento' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Iniciativas de Memoria Histórica', 'IMH', 11, '31', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Iniciativas de Memoria Histórica' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Reparaciones', 'REP', 12, '31', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Reparaciones' AND habilitado = 1);

-- Para DAV (32)
INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Esclarecimiento del fenómeno paramilitar', 'EFP', 20, '32', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Esclarecimiento del fenómeno paramilitar' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Contribuciones Voluntarias', 'CV', 21, '32', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Contribuciones Voluntarias' AND habilitado = 1);

-- Para DADH (33)
INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Testimonios', 'TEST', 30, '33', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Testimonios' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Fondos documentales', 'FD', 31, '33', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Fondos documentales' AND habilitado = 1);

-- Para Otros (Estrategias: 34,35,36,37,38,39,40)
INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Estrategia de Comunicaciones', 'EC', 40, 'otros', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Estrategia de Comunicaciones' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Dirección General', 'DG', 41, 'otros', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Dirección General' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Estrategia de Pedagogía', 'EP', 42, 'otros', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Estrategia de Pedagogía' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Estrategia de Enfoques Diferenciales, Pueblos Étnicos y Campesinado', 'EEDPEC', 43, 'otros', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Estrategia de Enfoques Diferenciales, Pueblos Étnicos y Campesinado' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Estrategia Psicosocial', 'EPS', 44, 'otros', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Estrategia Psicosocial' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Estrategia de Territorialización', 'ET', 45, 'otros', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Estrategia de Territorialización' AND habilitado = 1);

INSERT INTO catalogos.cat_item (id_cat, descripcion, abreviado, orden, otro, habilitado)
SELECT 18, 'Testimonio allegado al CNMH', 'TACNMH', 46, 'otros', 1
WHERE NOT EXISTS (SELECT 1 FROM catalogos.cat_item WHERE id_cat = 18 AND descripcion = 'Testimonio allegado al CNMH' AND habilitado = 1);
