-- =============================================
-- MIGRACION: Crear catalogo de Formato del Testimonio
-- Este script crea un nuevo catalogo (id=100) para los formatos
-- del testimonio: Audio, Audiovisual, Escrito, Otra indole
-- =============================================

-- Primero verificar si ya existe el catalogo 100
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM catalogos.cat_cat WHERE id_cat = 100) THEN
        INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable)
        VALUES (100, 'Formato del Testimonio', 'Formato en que fueron producidos los documentos (Audio, Audiovisual, Escrito, Otra indole)', 0);
    END IF;
END $$;

-- Eliminar items existentes del catalogo 100 si los hay
DELETE FROM catalogos.cat_item WHERE id_cat = 100;

-- Insertar los formatos correctos
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, habilitado) VALUES
(1001, 100, 'Audio', 'AUD', 1, 1),
(1002, 100, 'Audiovisual', 'AV', 2, 1),
(1003, 100, 'Escrito', 'ESC', 3, 1),
(1004, 100, 'Otra indole', 'OTR', 4, 1)
ON CONFLICT (id_item) DO UPDATE SET
    descripcion = EXCLUDED.descripcion,
    abreviado = EXCLUDED.abreviado,
    orden = EXCLUDED.orden;

-- Actualizar secuencia
SELECT setval('catalogos.cat_cat_id_cat_seq', GREATEST((SELECT MAX(id_cat) FROM catalogos.cat_cat), 100));
SELECT setval('catalogos.cat_item_id_item_seq', GREATEST((SELECT MAX(id_item) FROM catalogos.cat_item), 1004));

-- Verificar que se insertaron correctamente
SELECT * FROM catalogos.cat_item WHERE id_cat = 100 ORDER BY orden;
