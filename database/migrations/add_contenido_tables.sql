-- =============================================
-- MIGRACION: Tablas de relacion de contenido
-- Ejecutar despues de init.sql
-- =============================================

-- Tabla de contenido del testimonio (principal)
CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_testimonio (
    id_contenido SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER UNIQUE REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    fecha_hechos_inicial DATE,
    fecha_hechos_final DATE,
    responsables_individuales TEXT,
    temas_abordados TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tablas de relacion multiple para contenido

CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_poblacion (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_poblacion INTEGER REFERENCES catalogos.cat_item(id_item)
);

CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_ocupacion (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_ocupacion INTEGER REFERENCES catalogos.cat_item(id_item)
);

CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_sexo (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_sexo INTEGER REFERENCES catalogos.cat_item(id_item)
);

CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_identidad_genero (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_identidad INTEGER REFERENCES catalogos.cat_item(id_item)
);

CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_orientacion_sexual (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_orientacion INTEGER REFERENCES catalogos.cat_item(id_item)
);

CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_etnia (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_etnia INTEGER REFERENCES catalogos.cat_item(id_item)
);

CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_rango_etario (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_rango INTEGER REFERENCES catalogos.cat_item(id_item)
);

CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_discapacidad (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_discapacidad INTEGER REFERENCES catalogos.cat_item(id_item)
);

CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_hecho_victimizante (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_hecho INTEGER REFERENCES catalogos.cat_item(id_item)
);

CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_responsable (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_responsable INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Tabla de lugares geograficos mencionados en el testimonio
CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_lugar (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_departamento INTEGER REFERENCES catalogos.geo(id_geo),
    id_municipio INTEGER REFERENCES catalogos.geo(id_geo),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_contenido_poblacion_ent ON esclarecimiento.contenido_poblacion(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_contenido_ocupacion_ent ON esclarecimiento.contenido_ocupacion(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_contenido_sexo_ent ON esclarecimiento.contenido_sexo(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_contenido_identidad_ent ON esclarecimiento.contenido_identidad_genero(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_contenido_orientacion_ent ON esclarecimiento.contenido_orientacion_sexual(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_contenido_etnia_ent ON esclarecimiento.contenido_etnia(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_contenido_rango_ent ON esclarecimiento.contenido_rango_etario(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_contenido_discapacidad_ent ON esclarecimiento.contenido_discapacidad(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_contenido_hecho_ent ON esclarecimiento.contenido_hecho_victimizante(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_contenido_responsable_ent ON esclarecimiento.contenido_responsable(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_contenido_lugar_ent ON esclarecimiento.contenido_lugar(id_e_ind_fvt);
