-- =============================================
-- MODULO ESCUCHA LITE - Script de Inicialización
-- Base de datos: testimonios
-- =============================================

-- Crear extensión UUID
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =============================================
-- CREAR ESQUEMAS
-- =============================================
CREATE SCHEMA IF NOT EXISTS esclarecimiento;
CREATE SCHEMA IF NOT EXISTS fichas;
CREATE SCHEMA IF NOT EXISTS catalogos;

-- =============================================
-- ESQUEMA: catalogos
-- =============================================

-- Tabla de catálogos maestros
CREATE TABLE catalogos.cat_cat (
    id_cat SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    editable INTEGER DEFAULT 1,
    id_reclasificado INTEGER REFERENCES catalogos.cat_cat(id_cat)
);

-- Tabla de items de catálogos
CREATE TABLE catalogos.cat_item (
    id_item SERIAL PRIMARY KEY,
    id_cat INTEGER NOT NULL REFERENCES catalogos.cat_cat(id_cat),
    descripcion VARCHAR(255) NOT NULL,
    abreviado VARCHAR(50),
    texto TEXT,
    orden INTEGER DEFAULT 0,
    predeterminado INTEGER DEFAULT 2,
    otro VARCHAR(255),
    habilitado INTEGER DEFAULT 1,
    pendiente_revisar INTEGER DEFAULT 0,
    id_entrevistador INTEGER,
    id_reclasificado INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Tabla de geografía (DIVIPOLA)
CREATE TABLE catalogos.geo (
    id_geo SERIAL PRIMARY KEY,
    id_padre INTEGER REFERENCES catalogos.geo(id_geo),
    nivel INTEGER NOT NULL, -- 1=país, 2=depto, 3=municipio
    descripcion VARCHAR(255) NOT NULL,
    id_tipo INTEGER,
    codigo VARCHAR(20),
    lat DECIMAL(10,7),
    lon DECIMAL(10,7),
    codigo_2 VARCHAR(20)
);

-- Tabla de criterios fijos (para opciones del sistema)
CREATE TABLE catalogos.criterio_fijo (
    id_opcion SERIAL PRIMARY KEY,
    id_grupo INTEGER NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    abreviado VARCHAR(50),
    orden INTEGER DEFAULT 0,
    habilitado INTEGER DEFAULT 1
);

-- =============================================
-- TABLA DE USUARIOS (Laravel estándar)
-- =============================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- ESQUEMA: esclarecimiento
-- =============================================

-- Tabla de entrevistadores
CREATE TABLE esclarecimiento.entrevistador (
    id_entrevistador SERIAL PRIMARY KEY,
    id_usuario INTEGER REFERENCES users(id),
    id_macroterritorio INTEGER REFERENCES catalogos.geo(id_geo),
    id_territorio INTEGER REFERENCES catalogos.geo(id_geo),
    numero_entrevistador INTEGER,
    id_ubicacion INTEGER REFERENCES catalogos.geo(id_geo),
    id_grupo INTEGER REFERENCES catalogos.criterio_fijo(id_opcion),
    id_nivel INTEGER REFERENCES catalogos.criterio_fijo(id_opcion),
    solo_lectura INTEGER DEFAULT 0,
    compromiso_reserva INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de entrevistas individuales
CREATE TABLE esclarecimiento.e_ind_fvt (
    id_e_ind_fvt SERIAL PRIMARY KEY,
    id_subserie INTEGER,
    id_entrevistador INTEGER REFERENCES esclarecimiento.entrevistador(id_entrevistador),
    id_macroterritorio INTEGER REFERENCES catalogos.geo(id_geo),
    id_territorio INTEGER REFERENCES catalogos.geo(id_geo),
    entrevista_codigo VARCHAR(50),
    entrevista_numero INTEGER,
    entrevista_correlativo INTEGER,
    entrevista_fecha DATE,
    numero_entrevistador INTEGER,
    hechos_del DATE,
    hechos_al DATE,
    hechos_lugar INTEGER REFERENCES catalogos.geo(id_geo),
    entrevista_lugar INTEGER REFERENCES catalogos.geo(id_geo),
    anotaciones TEXT,
    titulo VARCHAR(500),
    -- Campos adicionales para wizard
    id_dependencia_origen INTEGER REFERENCES catalogos.cat_item(id_item),
    id_equipo_estrategia INTEGER REFERENCES catalogos.cat_item(id_item),
    nombre_proyecto VARCHAR(500),
    id_tipo_testimonio INTEGER REFERENCES catalogos.cat_item(id_item),
    num_testimoniantes INTEGER DEFAULT 1,
    fecha_toma_inicial DATE,
    fecha_toma_final DATE,
    id_idioma INTEGER REFERENCES catalogos.cat_item(id_item),
    tiene_anexos INTEGER DEFAULT 0,
    descripcion_anexos TEXT,
    observaciones_toma TEXT,
    seguimiento_revisado VARCHAR(50),
    seguimiento_finalizado INTEGER DEFAULT 0,
    metadatos_ce JSONB,
    metadatos_ca JSONB,
    metadatos_da JSONB,
    metadatos_ac JSONB,
    nna INTEGER DEFAULT 0,
    tiempo_entrevista INTEGER,
    clasifica_nna INTEGER,
    clasifica_sex INTEGER,
    clasifica_res INTEGER,
    clasifica_nivel INTEGER,
    clasifica_r1 INTEGER DEFAULT 0,
    clasifica_r2 INTEGER DEFAULT 0,
    html_transcripcion TEXT,
    json_etiquetado JSONB,
    fts TEXT,
    id_cerrado INTEGER,
    fichas_alarmas JSONB,
    fichas_estado INTEGER,
    es_virtual INTEGER DEFAULT 0,
    id_transcrita INTEGER,
    id_etiquetada INTEGER,
    id_activo INTEGER DEFAULT 1,
    id_remitido INTEGER,
    id_prioritario INTEGER,
    prioritario_tema TEXT,
    id_sector INTEGER REFERENCES catalogos.cat_item(id_item),
    id_etnico INTEGER REFERENCES catalogos.cat_item(id_item),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    insert_fh TIMESTAMP,
    insert_ip VARCHAR(45),
    insert_ent INTEGER,
    update_fh TIMESTAMP,
    update_ip VARCHAR(45),
    update_ent INTEGER,
    -- Columnas de procesamiento de transcripción
    transcripcion_completada_at TIMESTAMP,
    transcripcion_aprobada_at TIMESTAMP,
    transcripcion_aprobada_por INTEGER,
    transcripcion_final_at TIMESTAMP,
    transcripcion_final_por INTEGER,
    entidades_detectadas_at TIMESTAMP,
    anonimizacion_completada_at TIMESTAMP,
    anonimizacion_final TEXT,
    anonimizacion_final_at TIMESTAMP,
    anonimizacion_final_por INTEGER
);

-- Tabla de adjuntos
CREATE TABLE esclarecimiento.adjunto (
    id_adjunto SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    ubicacion VARCHAR(500),
    nombre_original VARCHAR(255),
    tipo_mime VARCHAR(100),
    id_tipo INTEGER REFERENCES catalogos.cat_item(id_item),
    id_calificacion INTEGER,
    tamano BIGINT,
    tamano_bruto BIGINT,
    md5 VARCHAR(32),
    liviano_ubicacion VARCHAR(500),
    liviano_tamano BIGINT,
    liviano_md5 VARCHAR(32),
    existe_archivo INTEGER DEFAULT 1,
    duracion INTEGER,
    texto_extraido TEXT,
    texto_extraido_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    insert_fh TIMESTAMP,
    insert_ip VARCHAR(45),
    insert_ent INTEGER,
    update_fh TIMESTAMP,
    update_ip VARCHAR(45),
    update_ent INTEGER
);

-- =============================================
-- ESQUEMA: fichas
-- =============================================

-- Tabla de personas
CREATE TABLE fichas.persona (
    id_persona SERIAL PRIMARY KEY,
    nombre VARCHAR(200),
    apellido VARCHAR(200),
    nombre_identitario VARCHAR(200),
    alias VARCHAR(100),
    fec_nac_a INTEGER,
    fec_nac_m INTEGER,
    fec_nac_d INTEGER,
    id_lugar_nacimiento INTEGER REFERENCES catalogos.geo(id_geo),
    id_lugar_nacimiento_depto INTEGER REFERENCES catalogos.geo(id_geo),
    id_sexo INTEGER REFERENCES catalogos.cat_item(id_item),
    id_orientacion INTEGER REFERENCES catalogos.cat_item(id_item),
    id_identidad INTEGER REFERENCES catalogos.cat_item(id_item),
    id_etnia INTEGER REFERENCES catalogos.cat_item(id_item),
    id_etnia_indigena INTEGER REFERENCES catalogos.cat_item(id_item),
    id_rango_etario INTEGER REFERENCES catalogos.cat_item(id_item),
    id_discapacidad INTEGER REFERENCES catalogos.cat_item(id_item),
    id_tipo_documento INTEGER REFERENCES catalogos.cat_item(id_item),
    num_documento VARCHAR(50),
    id_nacionalidad INTEGER REFERENCES catalogos.cat_item(id_item),
    id_otra_nacionalidad INTEGER REFERENCES catalogos.cat_item(id_item),
    id_estado_civil INTEGER REFERENCES catalogos.cat_item(id_item),
    id_lugar_residencia INTEGER REFERENCES catalogos.geo(id_geo),
    id_lugar_residencia_muni INTEGER REFERENCES catalogos.geo(id_geo),
    id_lugar_residencia_depto INTEGER REFERENCES catalogos.geo(id_geo),
    lugar_residencia_nombre_vereda VARCHAR(200),
    id_zona INTEGER REFERENCES catalogos.cat_item(id_item),
    telefono VARCHAR(50),
    correo_electronico VARCHAR(100),
    id_edu_formal INTEGER REFERENCES catalogos.cat_item(id_item),
    profesion VARCHAR(200),
    ocupacion_actual VARCHAR(200),
    id_ocupacion_actual INTEGER REFERENCES catalogos.cat_item(id_item),
    cargo_publico INTEGER DEFAULT 0,
    cargo_publico_cual VARCHAR(200),
    id_fuerza_publica_estado INTEGER REFERENCES catalogos.cat_item(id_item),
    fuerza_publica_especificar VARCHAR(200),
    id_fuerza_publica INTEGER REFERENCES catalogos.cat_item(id_item),
    id_actor_armado INTEGER REFERENCES catalogos.cat_item(id_item),
    actor_armado_especificar VARCHAR(200),
    organizacion_colectivo INTEGER DEFAULT 0,
    nombre_organizacion VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    insert_fh TIMESTAMP,
    insert_ip VARCHAR(45),
    insert_ent INTEGER,
    update_fh TIMESTAMP,
    update_ip VARCHAR(45),
    update_ent INTEGER
);

-- Tabla de poblaciones de persona (relación muchos a muchos)
CREATE TABLE fichas.persona_poblacion (
    id SERIAL PRIMARY KEY,
    id_persona INTEGER REFERENCES fichas.persona(id_persona) ON DELETE CASCADE,
    id_poblacion INTEGER REFERENCES catalogos.cat_item(id_item),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(id_persona, id_poblacion)
);

-- Tabla de ocupaciones de persona (relación muchos a muchos)
CREATE TABLE fichas.persona_ocupacion (
    id SERIAL PRIMARY KEY,
    id_persona INTEGER REFERENCES fichas.persona(id_persona) ON DELETE CASCADE,
    id_ocupacion INTEGER REFERENCES catalogos.cat_item(id_item),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(id_persona, id_ocupacion)
);

-- Tabla de consentimiento/entrevista
CREATE TABLE fichas.entrevista (
    id_entrevista SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    id_idioma INTEGER REFERENCES catalogos.cat_item(id_item),
    id_nativo INTEGER REFERENCES catalogos.cat_item(id_item),
    nombre_interprete VARCHAR(200),
    documentacion_aporta INTEGER,
    documentacion_especificar VARCHAR(500),
    identifica_testigos INTEGER,
    ampliar_relato INTEGER,
    ampliar_relato_temas VARCHAR(500),
    priorizar_entrevista INTEGER,
    priorizar_entrevista_asuntos VARCHAR(500),
    contiene_patrones INTEGER,
    contiene_patrones_cuales VARCHAR(500),
    indicaciones_transcripcion VARCHAR(500),
    observaciones TEXT,
    identificacion_consentimiento INTEGER,
    conceder_entrevista INTEGER,
    grabar_audio INTEGER,
    grabar_video INTEGER,
    tomar_fotografia INTEGER,
    elaborar_informe INTEGER,
    tratamiento_datos_analizar INTEGER,
    tratamiento_datos_analizar_sensible INTEGER,
    tratamiento_datos_utilizar INTEGER,
    tratamiento_datos_utilizar_sensible INTEGER,
    tratamiento_datos_publicar INTEGER,
    divulgar_material INTEGER,
    traslado_info INTEGER,
    compartir_info INTEGER,
    nombre_autoridad_etnica VARCHAR(200),
    nombre_identitario VARCHAR(200),
    pueblo_representado VARCHAR(200),
    id_pueblo_representado INTEGER REFERENCES catalogos.cat_item(id_item),
    asistencia INTEGER,
    restrictiva INTEGER,
    borrable INTEGER DEFAULT 0,
    consentimiento_nombres VARCHAR(200),
    consentimiento_apellidos VARCHAR(200),
    consentimiento_sexo INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    insert_fh TIMESTAMP,
    insert_ip VARCHAR(45),
    insert_ent INTEGER,
    update_fh TIMESTAMP,
    update_ip VARCHAR(45),
    update_ent INTEGER
);

-- Tabla de persona entrevistada (relación persona-entrevista)
CREATE TABLE fichas.persona_entrevistada (
    id_persona_entrevistada SERIAL PRIMARY KEY,
    id_persona INTEGER REFERENCES fichas.persona(id_persona),
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    es_victima INTEGER DEFAULT 0,
    es_testigo INTEGER DEFAULT 0,
    es_familiar INTEGER DEFAULT 0,
    edad INTEGER,
    sintesis_relato TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    insert_fh TIMESTAMP,
    insert_ip VARCHAR(45),
    insert_ent INTEGER,
    update_fh TIMESTAMP,
    update_ip VARCHAR(45),
    update_ent INTEGER
);

-- Tabla de consentimiento informado
CREATE TABLE fichas.consentimiento_informado (
    id_consentimiento SERIAL PRIMARY KEY,
    id_persona_entrevistada INTEGER REFERENCES fichas.persona_entrevistada(id_persona_entrevistada) ON DELETE CASCADE,
    tiene_documento_autorizacion BOOLEAN DEFAULT FALSE,
    es_menor_edad BOOLEAN DEFAULT FALSE,
    autoriza_ser_entrevistado BOOLEAN DEFAULT FALSE,
    permite_grabacion BOOLEAN DEFAULT FALSE,
    permite_procesamiento_misional BOOLEAN DEFAULT FALSE,
    permite_uso_conservacion_consulta BOOLEAN DEFAULT FALSE,
    considera_riesgo_seguridad BOOLEAN DEFAULT FALSE,
    autoriza_datos_personales_sin_anonimizar BOOLEAN DEFAULT FALSE,
    autoriza_datos_sensibles_sin_anonimizar BOOLEAN DEFAULT FALSE,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLA: traza_actividad (auditoría)
-- =============================================
CREATE TABLE traza_actividad (
    id_traza_actividad BIGSERIAL PRIMARY KEY,
    fecha_hora TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP,
    id_usuario INTEGER REFERENCES users(id),
    accion VARCHAR(100),
    objeto VARCHAR(100),
    id_registro INTEGER,
    referencia VARCHAR(500),
    codigo VARCHAR(100),
    ip VARCHAR(45),
    id_personificador INTEGER REFERENCES users(id)
);

-- =============================================
-- TABLA: permisos
-- =============================================
CREATE TABLE esclarecimiento.permiso (
    id_permiso SERIAL PRIMARY KEY,
    id_entrevistador INTEGER REFERENCES esclarecimiento.entrevistador(id_entrevistador),
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    id_tipo INTEGER DEFAULT 1, -- 1=lectura, 2=escritura, 3=completo
    fecha_otorgado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento TIMESTAMP,
    justificacion TEXT,
    id_otorgado_por INTEGER REFERENCES esclarecimiento.entrevistador(id_entrevistador),
    id_estado INTEGER DEFAULT 1, -- 1=vigente, 2=revocado
    fecha_desde DATE,
    fecha_hasta DATE,
    id_adjunto INTEGER,
    id_revocado_por INTEGER REFERENCES esclarecimiento.entrevistador(id_entrevistador),
    fecha_revocado TIMESTAMP,
    codigo_entrevista VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLAS DE PROCESAMIENTO
-- =============================================

-- Asignaciones de transcripción
CREATE TABLE esclarecimiento.asignacion_transcripcion (
    id_asignacion SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    id_transcriptor INTEGER REFERENCES esclarecimiento.entrevistador(id_entrevistador),
    id_asignado_por INTEGER,
    estado VARCHAR(50),
    fecha_asignacion TIMESTAMP,
    fecha_inicio_edicion TIMESTAMP,
    fecha_envio_revision TIMESTAMP,
    fecha_revision TIMESTAMP,
    id_revisor INTEGER,
    comentario_revision TEXT,
    transcripcion_editada TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_asignacion_transcripcion_estado ON esclarecimiento.asignacion_transcripcion(estado);
CREATE INDEX idx_asignacion_transcripcion_transcriptor ON esclarecimiento.asignacion_transcripcion(id_transcriptor);

-- Asignaciones de anonimización
CREATE TABLE esclarecimiento.asignacion_anonimizacion (
    id_asignacion SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    id_anonimizador INTEGER REFERENCES esclarecimiento.entrevistador(id_entrevistador),
    id_asignado_por INTEGER,
    estado VARCHAR(50),
    fecha_asignacion TIMESTAMP,
    fecha_inicio_edicion TIMESTAMP,
    fecha_envio_revision TIMESTAMP,
    fecha_revision TIMESTAMP,
    id_revisor INTEGER,
    comentario_revision TEXT,
    tipos_anonimizar VARCHAR(100),
    formato_reemplazo VARCHAR(50),
    texto_anonimizado TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_asignacion_anonimizacion_estado ON esclarecimiento.asignacion_anonimizacion(estado);
CREATE INDEX idx_asignacion_anonimizacion_anonimizador ON esclarecimiento.asignacion_anonimizacion(id_anonimizador);

-- Cola de trabajos de procesamiento (transcripción IA, NER, etc.)
CREATE TABLE esclarecimiento.trabajo_procesamiento (
    id_trabajo SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    tipo VARCHAR(50),
    estado VARCHAR(50),
    progreso INTEGER DEFAULT 0,
    parametros JSONB,
    id_usuario INTEGER,
    mensaje TEXT,
    resultado JSONB,
    iniciado_at TIMESTAMP,
    completado_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_trabajo_procesamiento_estado ON esclarecimiento.trabajo_procesamiento(estado);

-- Entidades detectadas (NER)
CREATE TABLE esclarecimiento.entidad_detectada (
    id_entidad SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    tipo VARCHAR(50),
    texto TEXT,
    texto_anonimizado VARCHAR(100),
    posicion_inicio INTEGER,
    posicion_fin INTEGER,
    confianza DECIMAL(5,4),
    verificado BOOLEAN DEFAULT FALSE,
    excluir_anonimizacion BOOLEAN DEFAULT FALSE,
    manual BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_entidad_detectada_entrevista ON esclarecimiento.entidad_detectada(id_e_ind_fvt);
CREATE INDEX idx_entidad_detectada_tipo ON esclarecimiento.entidad_detectada(tipo);


-- =============================================
-- TABLAS PIVOT: Relaciones múltiples de entrevista
-- =============================================

-- Formatos del testimonio (Audio, Audiovisual, Escrito, etc.)
CREATE TABLE esclarecimiento.entrevista_formato (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_formato INTEGER REFERENCES catalogos.cat_item(id_item),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Modalidades (Virtual, Presencial)
CREATE TABLE esclarecimiento.entrevista_modalidad (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_modalidad INTEGER REFERENCES catalogos.cat_item(id_item),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Necesidades de reparación
CREATE TABLE esclarecimiento.entrevista_necesidad_reparacion (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_necesidad INTEGER REFERENCES catalogos.cat_item(id_item),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLAS PIVOT: Contenido del testimonio
-- =============================================

-- Tabla principal de contenido
CREATE TABLE esclarecimiento.contenido_testimonio (
    id_contenido SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER UNIQUE REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    fecha_hechos_inicial DATE,
    fecha_hechos_final DATE,
    responsables_individuales TEXT,
    temas_abordados TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Poblaciones mencionadas
CREATE TABLE esclarecimiento.contenido_poblacion (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_poblacion INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Ocupaciones mencionadas
CREATE TABLE esclarecimiento.contenido_ocupacion (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_ocupacion INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Sexos mencionados
CREATE TABLE esclarecimiento.contenido_sexo (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_sexo INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Identidades de género
CREATE TABLE esclarecimiento.contenido_identidad_genero (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_identidad INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Orientaciones sexuales
CREATE TABLE esclarecimiento.contenido_orientacion_sexual (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_orientacion INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Etnias
CREATE TABLE esclarecimiento.contenido_etnia (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_etnia INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Rangos etarios
CREATE TABLE esclarecimiento.contenido_rango_etario (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_rango INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Discapacidades
CREATE TABLE esclarecimiento.contenido_discapacidad (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_discapacidad INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Hechos victimizantes
CREATE TABLE esclarecimiento.contenido_hecho_victimizante (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_hecho INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Responsables colectivos
CREATE TABLE esclarecimiento.contenido_responsable (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_responsable INTEGER REFERENCES catalogos.cat_item(id_item)
);

-- Lugares geográficos mencionados
CREATE TABLE esclarecimiento.contenido_lugar (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_departamento INTEGER REFERENCES catalogos.geo(id_geo),
    id_municipio INTEGER REFERENCES catalogos.geo(id_geo),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Áreas compatibles con el testimonio (relación muchos a muchos)
CREATE TABLE esclarecimiento.entrevista_area_compatible (
    id SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    id_area INTEGER REFERENCES catalogos.cat_item(id_item),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(id_e_ind_fvt, id_area)
);

-- =============================================
-- ÍNDICES
-- =============================================
CREATE INDEX idx_entrevista_codigo ON esclarecimiento.e_ind_fvt(entrevista_codigo);
CREATE INDEX idx_entrevista_fecha ON esclarecimiento.e_ind_fvt(entrevista_fecha);
CREATE INDEX idx_entrevista_entrevistador ON esclarecimiento.e_ind_fvt(id_entrevistador);
CREATE INDEX idx_entrevista_activo ON esclarecimiento.e_ind_fvt(id_activo);
CREATE INDEX idx_adjunto_entrevista ON esclarecimiento.adjunto(id_e_ind_fvt);
CREATE INDEX idx_area_compatible_entrevista ON esclarecimiento.entrevista_area_compatible(id_e_ind_fvt);
CREATE INDEX idx_persona_nombre ON fichas.persona(nombre, apellido);
CREATE INDEX idx_persona_documento ON fichas.persona(num_documento);
CREATE INDEX idx_persona_poblacion_persona ON fichas.persona_poblacion(id_persona);
CREATE INDEX idx_persona_ocupacion_persona ON fichas.persona_ocupacion(id_persona);
CREATE INDEX idx_consentimiento_persona ON fichas.consentimiento_informado(id_persona_entrevistada);
CREATE INDEX idx_cat_item_cat ON catalogos.cat_item(id_cat);
CREATE INDEX idx_geo_padre ON catalogos.geo(id_padre);
CREATE INDEX idx_traza_usuario ON traza_actividad(id_usuario);
CREATE INDEX idx_traza_fecha ON traza_actividad(fecha_hora);

-- =============================================
-- DATOS INICIALES: Criterios fijos (niveles de usuario)
-- =============================================
INSERT INTO catalogos.criterio_fijo (id_opcion, id_grupo, descripcion, abreviado, orden) VALUES
(1, 1, 'Administrador', 'Admin', 1),
(2, 1, 'Líder', 'Líder', 2),
(3, 1, 'Entrevistador', 'Ent', 3),
(4, 1, 'Transcriptor', 'Trans', 4),
(99, 1, 'Deshabilitado', 'Des', 99);

-- Acciones de auditoría
INSERT INTO catalogos.criterio_fijo (id_opcion, id_grupo, descripcion, abreviado, orden) VALUES
(21, 21, 'Crear', 'C', 1),
(22, 21, 'Leer', 'R', 2),
(23, 21, 'Actualizar', 'U', 3),
(24, 21, 'Eliminar', 'D', 4),
(25, 21, 'Login', 'L', 5),
(26, 21, 'Logout', 'O', 6);

-- Objetos de auditoría
INSERT INTO catalogos.criterio_fijo (id_opcion, id_grupo, descripcion, abreviado, orden) VALUES
(31, 22, 'Entrevista', 'Ent', 1),
(32, 22, 'Persona', 'Per', 2),
(33, 22, 'Adjunto', 'Adj', 3),
(34, 22, 'Usuario', 'Usr', 4),
(35, 22, 'Permiso', 'Prm', 5);

-- =============================================
-- DATOS INICIALES: Catálogos básicos
-- =============================================

-- Catálogo de Sexo
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(1, 'Sexo', 'Sexo biológico', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(1, 1, 'Hombre', 'H', 1),
(2, 1, 'Mujer', 'M', 2),
(3, 1, 'Intersexual', 'I', 3);

-- Catálogo de Tipo de Documento
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(2, 'Tipo de Documento', 'Tipos de documento de identidad', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(10, 2, 'Cédula de Ciudadanía', 'CC', 1),
(11, 2, 'Tarjeta de Identidad', 'TI', 2),
(12, 2, 'Cédula de Extranjería', 'CE', 3),
(13, 2, 'Pasaporte', 'PA', 4),
(14, 2, 'Registro Civil', 'RC', 5),
(15, 2, 'Sin Documento', 'SD', 6);

-- Catálogo de Etnia
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(3, 'Grupo Étnico', 'Grupos étnicos', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(20, 3, 'Comunidades negras', 'CN', 1),
(21, 3, 'Pueblos indígenas', 'PI', 2),
(22, 3, 'Palenqueras', 'PA', 3),
(23, 3, 'Raizales', 'RA', 4),
(24, 3, 'Pueblo Rrom', 'RR', 5),
(25, 3, 'Ningún grupo étnico', 'NG', 6);

-- Catálogo de Dependencia de Origen
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(4, 'Dependencia de Origen', 'Áreas que realizaron la toma del testimonio', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(30, 4, 'Dirección Museo de Memoria y Conflicto', 'DMMC', 1),
(31, 4, 'Dirección de Construcción de Memoria Histórica', 'DCMH', 2),
(32, 4, 'Dirección de Acuerdos de la Verdad', 'DAV', 3),
(33, 4, 'Dirección de Archivo de los Derechos Humanos', 'DADH', 4),
(34, 4, 'Estrategia de Comunicaciones', 'EC', 5),
(35, 4, 'Dirección General', 'DG', 6),
(36, 4, 'Estrategia de Pedagogía', 'EP', 7),
(37, 4, 'Estrategia de Enfoques Diferenciales', 'EED', 8),
(38, 4, 'Estrategia Psicosocial', 'EPS', 9),
(39, 4, 'Estrategia de Territorialización', 'ET', 10),
(40, 4, 'Testimonio allegado al CNMH', 'TA', 11);

-- Catálogo de Tipo de Testimonio
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(5, 'Tipo de Testimonio', 'Clasificación según enfoque del testimonio', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(50, 5, 'Entrevista Individual', 'EI', 1),
(51, 5, 'Entrevista grupal/colectiva', 'EG', 2),
(52, 5, 'Entrevista a Profundidad', 'EP', 3),
(53, 5, 'Entrevista Estructurada', 'EE', 4),
(54, 5, 'Entrevista de Ampliación', 'EA', 5);

-- Catálogo de Formato del Testimonio
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(6, 'Formato del Testimonio', 'Formato en que fueron producidos los documentos', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(60, 6, 'Audio', 'AUD', 1),
(61, 6, 'Audiovisual', 'AV', 2),
(62, 6, 'Escrito', 'ESC', 3),
(63, 6, 'Otra índole', 'OTR', 4);

-- Catálogo de Modalidad
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(7, 'Modalidad', 'Forma en que se llevó a cabo la entrevista', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(70, 7, 'Virtual', 'VIR', 1),
(71, 7, 'Presencial', 'PRE', 2),
(72, 7, 'Sin Información', 'SI', 3);

-- Catálogo de Idiomas
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(8, 'Idioma', 'Idiomas del testimonio', 1);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(80, 8, 'Español', 'ES', 1),
(81, 8, 'Inglés', 'EN', 2),
(82, 8, 'Lengua nativa', 'LN', 3);

-- Catálogo de Poblaciones
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(9, 'Población', 'Grupos sociales o comunitarios', 1);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(90, 9, 'Líderes y/o lideresas', 'LID', 1),
(91, 9, 'Personas refugiadas', 'REF', 2),
(92, 9, 'Personas inmigrantes', 'INM', 3),
(93, 9, 'Personas exiliadas', 'EXI', 4),
(94, 9, 'Habitantes de calle', 'HAB', 5),
(95, 9, 'Personas desmovilizadas', 'DES', 6),
(96, 9, 'Menores desvinculados', 'MEN', 7),
(97, 9, 'Personas privadas de la libertad', 'PPL', 8),
(98, 9, 'Sindicalistas', 'SIN', 9),
(99, 9, 'Víctimas del conflicto armado', 'VIC', 10),
(100, 9, 'Ex miembro de Fuerza Pública', 'EFP', 11);

-- Catálogo de Hechos Victimizantes
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(10, 'Hecho Victimizante', 'Tipos de hechos victimizantes', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(110, 10, 'Acciones Bélicas', 'AB', 1),
(111, 10, 'Asesinatos Selectivos', 'AS', 2),
(112, 10, 'Atentado Terrorista', 'AT', 3),
(113, 10, 'Daño a Bienes Civiles', 'DB', 4),
(114, 10, 'Desaparición Forzada', 'DF', 5),
(115, 10, 'Masacres', 'MA', 6),
(116, 10, 'Reclutamiento de Menores', 'RU', 7),
(117, 10, 'Secuestro', 'SE', 8),
(118, 10, 'Violencia Sexual', 'VS', 9),
(119, 10, 'Ataque a Poblado', 'AP', 10),
(120, 10, 'Minas', 'MI', 11),
(121, 10, 'Desplazamiento forzado', 'DF', 12);

-- Catálogo de Ocupaciones
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(11, 'Ocupación', 'Ocupaciones u oficios', 1);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(130, 11, 'Agricultor/Campesino', 'AGR', 1),
(131, 11, 'Comerciante', 'COM', 2),
(132, 11, 'Docente', 'DOC', 3),
(133, 11, 'Empleado', 'EMP', 4),
(134, 11, 'Estudiante', 'EST', 5),
(135, 11, 'Hogar', 'HOG', 6),
(136, 11, 'Independiente', 'IND', 7),
(137, 11, 'Profesional', 'PRO', 8),
(138, 11, 'Desempleado', 'DES', 9),
(139, 11, 'Otro', 'OTR', 10);

-- Catálogo de Identidad de Género
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(12, 'Identidad de Género', 'Identidades de género', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(140, 12, 'Masculino', 'MAS', 1),
(141, 12, 'Femenino', 'FEM', 2),
(142, 12, 'Transgénero', 'TRA', 3),
(143, 12, 'No binario', 'NBI', 4),
(144, 12, 'Otro', 'OTR', 5),
(145, 12, 'Sin información', 'SI', 6);

-- Catálogo de Orientación Sexual
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(13, 'Orientación Sexual', 'Orientaciones sexuales', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(150, 13, 'Heterosexual', 'HET', 1),
(151, 13, 'Homosexual', 'HOM', 2),
(152, 13, 'Bisexual', 'BIS', 3),
(153, 13, 'Otra', 'OTR', 4),
(154, 13, 'Sin información', 'SI', 5);

-- Catálogo de Rangos Etarios
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(14, 'Rango Etario', 'Rangos de edad', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(160, 14, '0-5 años (Primera infancia)', 'PI', 1),
(161, 14, '6-11 años (Infancia)', 'INF', 2),
(162, 14, '12-17 años (Adolescencia)', 'ADO', 3),
(163, 14, '18-28 años (Juventud)', 'JUV', 4),
(164, 14, '29-59 años (Adultez)', 'ADU', 5),
(165, 14, '60+ años (Vejez)', 'VEJ', 6),
(166, 14, 'Sin información', 'SI', 7);

-- Catálogo de Discapacidades
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(15, 'Discapacidad', 'Tipos de discapacidad', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(170, 15, 'Ninguna', 'NIN', 1),
(171, 15, 'Física', 'FIS', 2),
(172, 15, 'Visual', 'VIS', 3),
(173, 15, 'Auditiva', 'AUD', 4),
(174, 15, 'Cognitiva', 'COG', 5),
(175, 15, 'Psicosocial', 'PSI', 6),
(176, 15, 'Múltiple', 'MUL', 7),
(177, 15, 'Sin información', 'SI', 8);

-- Catálogo de Necesidades de Ruta de Reparación
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(16, 'Necesidad de Ruta de Reparación', 'Necesidades de ruta de reparación', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(180, 16, 'Administrativa', 'ADM', 1),
(181, 16, 'Simbólica', 'SIM', 2),
(182, 16, 'No', 'NO', 3),
(183, 16, 'No Aplica', 'NA', 4);

-- Catálogo de Responsables Colectivos
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(17, 'Responsable Colectivo', 'Grupos armados y otros responsables', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(190, 17, 'Guerrillas', 'GUE', 1),
(191, 17, 'Paramilitares', 'PAR', 2),
(192, 17, 'Fuerza Pública', 'FPU', 3),
(193, 17, 'Agentes del Estado', 'AES', 4),
(194, 17, 'Grupos post-desmovilización', 'GPD', 5),
(195, 17, 'Desconocido', 'DES', 6),
(196, 17, 'Otro', 'OTR', 7);

-- Catálogo de Equipos/Estrategias (relacionados con Dependencias)
-- El campo 'otro' indica el id_item de la dependencia padre, o 'otros' para estrategias generales
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(18, 'Equipo/Estrategia', 'Equipos o estrategias por dependencia', 1);

-- Para DMMC - Dirección Museo de Memoria y Conflicto (id_item=30)
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, otro) VALUES
(200, 18, 'Dimensión Física', 'DF', 1, '30'),
(201, 18, 'Dimensión Territorial', 'DT', 2, '30'),
(202, 18, 'Dimensión Virtual', 'DV', 3, '30');

-- Para DCMH - Dirección de Construcción de Memoria Histórica (id_item=31)
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, otro) VALUES
(210, 18, 'Investigación para el Esclarecimiento', 'IPE', 10, '31'),
(211, 18, 'Iniciativas de Memoria Histórica', 'IMH', 11, '31'),
(212, 18, 'Reparaciones', 'REP', 12, '31');

-- Para DAV - Dirección de Acuerdos de la Verdad (id_item=32)
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, otro) VALUES
(220, 18, 'Esclarecimiento del fenómeno paramilitar', 'EFP', 20, '32'),
(221, 18, 'Contribuciones Voluntarias', 'CV', 21, '32');

-- Para DADH - Dirección de Archivo de los Derechos Humanos (id_item=33)
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, otro) VALUES
(230, 18, 'Testimonios', 'TEST', 30, '33'),
(231, 18, 'Fondos documentales', 'FD', 31, '33');

-- Para Otros (Estrategias: 34,35,36,37,38,39,40) - otro='otros'
INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden, otro) VALUES
(240, 18, 'Estrategia de Comunicaciones', 'EC', 40, 'otros'),
(250, 18, 'Dirección General', 'DG', 41, 'otros'),
(260, 18, 'Estrategia de Pedagogía', 'EP', 42, 'otros'),
(270, 18, 'Estrategia de Enfoques Diferenciales, Pueblos Étnicos y Campesinado', 'EEDPEC', 43, 'otros'),
(280, 18, 'Estrategia Psicosocial', 'EPS', 44, 'otros'),
(290, 18, 'Estrategia de Territorialización', 'ET', 45, 'otros'),
(300, 18, 'Testimonio allegado al CNMH', 'TACNMH', 46, 'otros');

-- Catálogo de Tipo de Archivo Adjunto
INSERT INTO catalogos.cat_cat (id_cat, nombre, descripcion, editable) VALUES
(19, 'Tipo de Archivo Adjunto', 'Tipos de archivo adjunto a la entrevista', 0);

INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, orden) VALUES
(310, 19, 'Audio/Video de la entrevista', 'AV', 1),
(311, 19, 'Consentimiento informado', 'CI', 2),
(312, 19, 'Transcripcion Automatizada', 'TA', 3),
(313, 19, 'Transcripcion final', 'TF', 4),
(314, 19, 'Version publica', 'VP', 5),
(315, 19, 'Otros Documentos', 'OD', 6);

-- =============================================
-- DATOS INICIALES: Geografía Colombia (básico)
-- =============================================
INSERT INTO catalogos.geo (id_geo, id_padre, nivel, descripcion, codigo) VALUES
(1, NULL, 1, 'Colombia', 'CO');

-- Departamentos principales
INSERT INTO catalogos.geo (id_geo, id_padre, nivel, descripcion, codigo) VALUES
(5, 1, 2, 'Antioquia', '05'),
(8, 1, 2, 'Atlántico', '08'),
(11, 1, 2, 'Bogotá D.C.', '11'),
(13, 1, 2, 'Bolívar', '13'),
(15, 1, 2, 'Boyacá', '15'),
(17, 1, 2, 'Caldas', '17'),
(18, 1, 2, 'Caquetá', '18'),
(19, 1, 2, 'Cauca', '19'),
(20, 1, 2, 'Cesar', '20'),
(23, 1, 2, 'Córdoba', '23'),
(25, 1, 2, 'Cundinamarca', '25'),
(27, 1, 2, 'Chocó', '27'),
(41, 1, 2, 'Huila', '41'),
(44, 1, 2, 'La Guajira', '44'),
(47, 1, 2, 'Magdalena', '47'),
(50, 1, 2, 'Meta', '50'),
(52, 1, 2, 'Nariño', '52'),
(54, 1, 2, 'Norte de Santander', '54'),
(63, 1, 2, 'Quindío', '63'),
(66, 1, 2, 'Risaralda', '66'),
(68, 1, 2, 'Santander', '68'),
(70, 1, 2, 'Sucre', '70'),
(73, 1, 2, 'Tolima', '73'),
(76, 1, 2, 'Valle del Cauca', '76'),
(81, 1, 2, 'Arauca', '81'),
(85, 1, 2, 'Casanare', '85'),
(86, 1, 2, 'Putumayo', '86'),
(88, 1, 2, 'San Andrés y Providencia', '88'),
(91, 1, 2, 'Amazonas', '91'),
(94, 1, 2, 'Guainía', '94'),
(95, 1, 2, 'Guaviare', '95'),
(97, 1, 2, 'Vaupés', '97'),
(99, 1, 2, 'Vichada', '99');

-- =============================================
-- MUNICIPIOS DE COLOMBIA (DIVIPOLA completo)
-- =============================================

-- Municipios de Colombia (DIVIPOLA)
-- Generado desde crear_bd.sql

INSERT INTO catalogos.geo (id_geo, id_padre, nivel, descripcion, codigo) VALUES
(1037, 5, 3, 'ABEJORRAL', '05002'),
(1038, 5, 3, 'ABRIAQUÍ', '05004'),
(1039, 5, 3, 'ALEJANDRÍA', '05021'),
(1040, 5, 3, 'AMAGÁ', '05030'),
(1041, 5, 3, 'AMALFI', '05031'),
(1042, 5, 3, 'ANDES', '05034'),
(1043, 5, 3, 'ANGELÓPOLIS', '05036'),
(1044, 5, 3, 'ANGOSTURA', '05038'),
(1045, 5, 3, 'ANORÍ', '05040'),
(1047, 5, 3, 'ANZÁ', '05044'),
(1048, 5, 3, 'APARTADÓ', '05045'),
(1049, 5, 3, 'ARBOLETES', '05051'),
(1050, 5, 3, 'ARGELIA', '05055'),
(1051, 5, 3, 'ARMENIA', '05059'),
(1052, 5, 3, 'BARBOSA', '05079'),
(1054, 5, 3, 'BELLO', '05088'),
(1053, 5, 3, 'BELMIRA', '05086'),
(1055, 5, 3, 'BETANIA', '05091'),
(1056, 5, 3, 'BETULIA', '05093'),
(1058, 5, 3, 'BRICEÑO', '05107'),
(1059, 5, 3, 'BURITICÁ', '05113'),
(1061, 5, 3, 'CAICEDO', '05125'),
(1062, 5, 3, 'CALDAS', '05129'),
(1063, 5, 3, 'CAMPAMENTO', '05134'),
(1065, 5, 3, 'CARACOLÍ', '05142'),
(1066, 5, 3, 'CARAMANTA', '05145'),
(1067, 5, 3, 'CAREPA', '05147'),
(1069, 5, 3, 'CAROLINA', '05150'),
(1070, 5, 3, 'CAUCASIA', '05154'),
(1064, 5, 3, 'CAÑASGORDAS', '05138'),
(1071, 5, 3, 'CHIGORODÓ', '05172'),
(1072, 5, 3, 'CISNEROS', '05190'),
(1057, 5, 3, 'CIUDAD BOLÍVAR', '05101'),
(1073, 5, 3, 'COCORNÁ', '05197'),
(1074, 5, 3, 'CONCEPCIÓN', '05206'),
(1075, 5, 3, 'CONCORDIA', '05209'),
(1076, 5, 3, 'COPACABANA', '05212'),
(1060, 5, 3, 'CÁCERES', '05120'),
(1077, 5, 3, 'DABEIBA', '05234'),
(1078, 5, 3, 'DONMATÍAS', '05237'),
(1079, 5, 3, 'EBÉJICO', '05240'),
(1080, 5, 3, 'EL BAGRE', '05250'),
(1068, 5, 3, 'EL CARMEN DE VIBORAL', '05148'),
(1139, 5, 3, 'EL SANTUARIO', '05697'),
(1081, 5, 3, 'ENTRERRÍOS', '05264'),
(1082, 5, 3, 'ENVIGADO', '05266'),
(1083, 5, 3, 'FREDONIA', '05282'),
(1084, 5, 3, 'FRONTINO', '05284'),
(1085, 5, 3, 'GIRALDO', '05306'),
(1086, 5, 3, 'GIRARDOTA', '05308'),
(1088, 5, 3, 'GRANADA', '05313'),
(1089, 5, 3, 'GUADALUPE', '05315'),
(1090, 5, 3, 'GUARNE', '05318'),
(1091, 5, 3, 'GUATAPÉ', '05321'),
(1087, 5, 3, 'GÓMEZ PLATA', '05310'),
(1092, 5, 3, 'HELICONIA', '05347'),
(1093, 5, 3, 'HISPANIA', '05353'),
(1094, 5, 3, 'ITAGÜÍ', '05360'),
(1095, 5, 3, 'ITUANGO', '05361'),
(1096, 5, 3, 'JARDÍN', '05364'),
(1097, 5, 3, 'JERICÓ', '05368'),
(1098, 5, 3, 'LA CEJA', '05376'),
(1099, 5, 3, 'LA ESTRELLA', '05380'),
(1100, 5, 3, 'LA PINTADA', '05390'),
(1101, 5, 3, 'LA UNIÓN', '05400'),
(1102, 5, 3, 'LIBORINA', '05411'),
(1103, 5, 3, 'MACEO', '05425'),
(1104, 5, 3, 'MARINILLA', '05440'),
(1036, 5, 3, 'MEDELLÍN', '05001'),
(1105, 5, 3, 'MONTEBELLO', '05467'),
(1106, 5, 3, 'MURINDÓ', '05475'),
(1107, 5, 3, 'MUTATÁ', '05480'),
(1108, 5, 3, 'NARIÑO', '05483'),
(1110, 5, 3, 'NECHÍ', '05495'),
(1109, 5, 3, 'NECOCLÍ', '05490'),
(1111, 5, 3, 'OLAYA', '05501'),
(1113, 5, 3, 'PEQUE', '05543'),
(1112, 5, 3, 'PEÑOL', '05541'),
(1114, 5, 3, 'PUEBLORRICO', '05576'),
(1115, 5, 3, 'PUERTO BERRÍO', '05579'),
(1116, 5, 3, 'PUERTO NARE', '05585'),
(1117, 5, 3, 'PUERTO TRIUNFO', '05591'),
(1118, 5, 3, 'REMEDIOS', '05604'),
(1119, 5, 3, 'RETIRO', '05607'),
(1120, 5, 3, 'RIONEGRO', '05615'),
(1121, 5, 3, 'SABANALARGA', '05628'),
(1122, 5, 3, 'SABANETA', '05631'),
(1123, 5, 3, 'SALGAR', '05642'),
(1124, 5, 3, 'SAN ANDRÉS DE CUERQUÍA', '05647'),
(1125, 5, 3, 'SAN CARLOS', '05649'),
(1126, 5, 3, 'SAN FRANCISCO', '05652'),
(1127, 5, 3, 'SAN JERÓNIMO', '05656'),
(1128, 5, 3, 'SAN JOSÉ DE LA MONTAÑA', '05658'),
(1129, 5, 3, 'SAN JUAN DE URABÁ', '05659'),
(1130, 5, 3, 'SAN LUIS', '05660'),
(1131, 5, 3, 'SAN PEDRO DE LOS MILAGROS', '05664'),
(1132, 5, 3, 'SAN PEDRO DE URABÁ', '05665'),
(1133, 5, 3, 'SAN RAFAEL', '05667'),
(1134, 5, 3, 'SAN ROQUE', '05670'),
(1135, 5, 3, 'SAN VICENTE FERRER', '05674'),
(1136, 5, 3, 'SANTA BÁRBARA', '05679'),
(1046, 5, 3, 'SANTA FÉ DE ANTIOQUIA', '05042'),
(1137, 5, 3, 'SANTA ROSA DE OSOS', '05686'),
(1138, 5, 3, 'SANTO DOMINGO', '05690'),
(1140, 5, 3, 'SEGOVIA', '05736'),
(1141, 5, 3, 'SONSÓN', '05756'),
(1142, 5, 3, 'SOPETRÁN', '05761'),
(1144, 5, 3, 'TARAZÁ', '05790'),
(1145, 5, 3, 'TARSO', '05792'),
(1146, 5, 3, 'TITIRIBÍ', '05809'),
(1147, 5, 3, 'TOLEDO', '05819'),
(1148, 5, 3, 'TURBO', '05837'),
(1143, 5, 3, 'TÁMESIS', '05789'),
(1149, 5, 3, 'URAMITA', '05842'),
(1150, 5, 3, 'URRAO', '05847'),
(1151, 5, 3, 'VALDIVIA', '05854'),
(1152, 5, 3, 'VALPARAÍSO', '05856'),
(1153, 5, 3, 'VEGACHÍ', '05858'),
(1154, 5, 3, 'VENECIA', '05861'),
(1155, 5, 3, 'VIGÍA DEL FUERTE', '05873'),
(1156, 5, 3, 'YALÍ', '05885'),
(1157, 5, 3, 'YARUMAL', '05887'),
(1158, 5, 3, 'YOLOMBÓ', '05890'),
(1159, 5, 3, 'YONDÓ', '05893'),
(1160, 5, 3, 'ZARAGOZA', '05895'),
(1162, 8, 3, 'BARANOA', '08078'),
(1161, 8, 3, 'BARRANQUILLA', '08001'),
(1163, 8, 3, 'CAMPO DE LA CRUZ', '08137'),
(1164, 8, 3, 'CANDELARIA', '08141'),
(1165, 8, 3, 'GALAPA', '08296'),
(1166, 8, 3, 'JUAN DE ACOSTA', '08372'),
(1167, 8, 3, 'LURUACO', '08421'),
(1168, 8, 3, 'MALAMBO', '08433'),
(1169, 8, 3, 'MANATÍ', '08436'),
(1170, 8, 3, 'PALMAR DE VARELA', '08520'),
(1171, 8, 3, 'PIOJÓ', '08549'),
(1172, 8, 3, 'POLONUEVO', '08558'),
(1173, 8, 3, 'PONEDERA', '08560'),
(1174, 8, 3, 'PUERTO COLOMBIA', '08573'),
(1175, 8, 3, 'REPELÓN', '08606'),
(1176, 8, 3, 'SABANAGRANDE', '08634'),
(1177, 8, 3, 'SABANALARGA', '08638'),
(1178, 8, 3, 'SANTA LUCÍA', '08675'),
(1179, 8, 3, 'SANTO TOMÁS', '08685'),
(1180, 8, 3, 'SOLEDAD', '08758'),
(1181, 8, 3, 'SUAN', '08770'),
(1182, 8, 3, 'TUBARÁ', '08832'),
(1183, 8, 3, 'USIACURÍ', '08849'),
(1184, 11, 3, 'BOGOTÁ, D.C.', '11001'),
(1186, 13, 3, 'ACHÍ', '13006'),
(1187, 13, 3, 'ALTOS DEL ROSARIO', '13030'),
(1188, 13, 3, 'ARENAL', '13042'),
(1189, 13, 3, 'ARJONA', '13052'),
(1190, 13, 3, 'ARROYOHONDO', '13062'),
(1191, 13, 3, 'BARRANCO DE LOBA', '13074'),
(1192, 13, 3, 'CALAMAR', '13140'),
(1193, 13, 3, 'CANTAGALLO', '13160'),
(1185, 13, 3, 'CARTAGENA DE INDIAS', '13001'),
(1194, 13, 3, 'CICUCO', '13188'),
(1196, 13, 3, 'CLEMENCIA', '13222'),
(1195, 13, 3, 'CÓRDOBA', '13212'),
(1197, 13, 3, 'EL CARMEN DE BOLÍVAR', '13244'),
(1198, 13, 3, 'EL GUAMO', '13248'),
(1199, 13, 3, 'EL PEÑÓN', '13268'),
(1200, 13, 3, 'HATILLO DE LOBA', '13300'),
(1201, 13, 3, 'MAGANGUÉ', '13430'),
(1202, 13, 3, 'MAHATES', '13433'),
(1203, 13, 3, 'MARGARITA', '13440'),
(1204, 13, 3, 'MARÍA LA BAJA', '13442'),
(1206, 13, 3, 'MOMPÓS', '13468'),
(1205, 13, 3, 'MONTECRISTO', '13458'),
(1207, 13, 3, 'MORALES', '13473'),
(1208, 13, 3, 'NOROSÍ', '13490'),
(1209, 13, 3, 'PINILLOS', '13549'),
(1210, 13, 3, 'REGIDOR', '13580'),
(1211, 13, 3, 'RÍO VIEJO', '13600'),
(1212, 13, 3, 'SAN CRISTÓBAL', '13620'),
(1213, 13, 3, 'SAN ESTANISLAO', '13647'),
(1214, 13, 3, 'SAN FERNANDO', '13650'),
(1215, 13, 3, 'SAN JACINTO', '13654'),
(1216, 13, 3, 'SAN JACINTO DEL CAUCA', '13655'),
(1217, 13, 3, 'SAN JUAN NEPOMUCENO', '13657'),
(1218, 13, 3, 'SAN MARTÍN DE LOBA', '13667'),
(1219, 13, 3, 'SAN PABLO', '13670'),
(1220, 13, 3, 'SANTA CATALINA', '13673'),
(1221, 13, 3, 'SANTA ROSA', '13683'),
(1222, 13, 3, 'SANTA ROSA DEL SUR', '13688'),
(1223, 13, 3, 'SIMITÍ', '13744'),
(1224, 13, 3, 'SOPLAVIENTO', '13760'),
(1225, 13, 3, 'TALAIGUA NUEVO', '13780'),
(1226, 13, 3, 'TIQUISIO', '13810'),
(1227, 13, 3, 'TURBACO', '13836'),
(1228, 13, 3, 'TURBANÁ', '13838'),
(1229, 13, 3, 'VILLANUEVA', '13873'),
(1230, 13, 3, 'ZAMBRANO', '13894'),
(1232, 15, 3, 'ALMEIDA', '15022'),
(1233, 15, 3, 'AQUITANIA', '15047'),
(1234, 15, 3, 'ARCABUCO', '15051'),
(1235, 15, 3, 'BELÉN', '15087'),
(1236, 15, 3, 'BERBEO', '15090'),
(1237, 15, 3, 'BETÉITIVA', '15092'),
(1238, 15, 3, 'BOAVITA', '15097'),
(1239, 15, 3, 'BOYACÁ', '15104'),
(1240, 15, 3, 'BRICEÑO', '15106'),
(1241, 15, 3, 'BUENAVISTA', '15109'),
(1242, 15, 3, 'BUSBANZÁ', '15114'),
(1243, 15, 3, 'CALDAS', '15131'),
(1244, 15, 3, 'CAMPOHERMOSO', '15135'),
(1245, 15, 3, 'CERINZA', '15162'),
(1246, 15, 3, 'CHINAVITA', '15172'),
(1247, 15, 3, 'CHIQUINQUIRÁ', '15176'),
(1248, 15, 3, 'CHISCAS', '15180'),
(1249, 15, 3, 'CHITA', '15183'),
(1250, 15, 3, 'CHITARAQUE', '15185'),
(1251, 15, 3, 'CHIVATÁ', '15187'),
(1261, 15, 3, 'CHIVOR', '15236'),
(1260, 15, 3, 'CHÍQUIZA', '15232'),
(1252, 15, 3, 'CIÉNEGA', '15189'),
(1254, 15, 3, 'COPER', '15212'),
(1255, 15, 3, 'CORRALES', '15215'),
(1256, 15, 3, 'COVARACHÍA', '15218'),
(1257, 15, 3, 'CUBARÁ', '15223'),
(1258, 15, 3, 'CUCAITA', '15224'),
(1259, 15, 3, 'CUÍTIVA', '15226'),
(1253, 15, 3, 'CÓMBITA', '15204'),
(1262, 15, 3, 'DUITAMA', '15238'),
(1263, 15, 3, 'EL COCUY', '15244'),
(1264, 15, 3, 'EL ESPINO', '15248'),
(1265, 15, 3, 'FIRAVITOBA', '15272'),
(1266, 15, 3, 'FLORESTA', '15276'),
(1267, 15, 3, 'GACHANTIVÁ', '15293'),
(1269, 15, 3, 'GARAGOA', '15299'),
(1270, 15, 3, 'GUACAMAYAS', '15317'),
(1271, 15, 3, 'GUATEQUE', '15322'),
(1272, 15, 3, 'GUAYATÁ', '15325'),
(1268, 15, 3, 'GÁMEZA', '15296'),
(1273, 15, 3, 'GÜICÁN DE LA SIERRA', '15332'),
(1274, 15, 3, 'IZA', '15362'),
(1275, 15, 3, 'JENESANO', '15367'),
(1276, 15, 3, 'JERICÓ', '15368'),
(1278, 15, 3, 'LA CAPILLA', '15380'),
(1280, 15, 3, 'LA UVITA', '15403'),
(1279, 15, 3, 'LA VICTORIA', '15401'),
(1277, 15, 3, 'LABRANZAGRANDE', '15377'),
(1282, 15, 3, 'MACANAL', '15425'),
(1283, 15, 3, 'MARIPÍ', '15442'),
(1284, 15, 3, 'MIRAFLORES', '15455'),
(1285, 15, 3, 'MONGUA', '15464'),
(1286, 15, 3, 'MONGUÍ', '15466'),
(1287, 15, 3, 'MONIQUIRÁ', '15469'),
(1288, 15, 3, 'MOTAVITA', '15476'),
(1289, 15, 3, 'MUZO', '15480'),
(1290, 15, 3, 'NOBSA', '15491'),
(1291, 15, 3, 'NUEVO COLÓN', '15494'),
(1292, 15, 3, 'OICATÁ', '15500'),
(1293, 15, 3, 'OTANCHE', '15507'),
(1294, 15, 3, 'PACHAVITA', '15511'),
(1296, 15, 3, 'PAIPA', '15516'),
(1297, 15, 3, 'PAJARITO', '15518'),
(1298, 15, 3, 'PANQUEBA', '15522'),
(1299, 15, 3, 'PAUNA', '15531'),
(1300, 15, 3, 'PAYA', '15533'),
(1301, 15, 3, 'PAZ DE RÍO', '15537'),
(1302, 15, 3, 'PESCA', '15542'),
(1303, 15, 3, 'PISBA', '15550'),
(1304, 15, 3, 'PUERTO BOYACÁ', '15572'),
(1295, 15, 3, 'PÁEZ', '15514'),
(1305, 15, 3, 'QUÍPAMA', '15580'),
(1306, 15, 3, 'RAMIRIQUÍ', '15599'),
(1308, 15, 3, 'RONDÓN', '15621'),
(1307, 15, 3, 'RÁQUIRA', '15600'),
(1309, 15, 3, 'SABOYÁ', '15632'),
(1311, 15, 3, 'SAMACÁ', '15646'),
(1312, 15, 3, 'SAN EDUARDO', '15660'),
(1313, 15, 3, 'SAN JOSÉ DE PARE', '15664'),
(1314, 15, 3, 'SAN LUIS DE GACENO', '15667'),
(1315, 15, 3, 'SAN MATEO', '15673'),
(1316, 15, 3, 'SAN MIGUEL DE SEMA', '15676'),
(1317, 15, 3, 'SAN PABLO DE BORBUR', '15681'),
(1319, 15, 3, 'SANTA MARÍA', '15690'),
(1320, 15, 3, 'SANTA ROSA DE VITERBO', '15693'),
(1321, 15, 3, 'SANTA SOFÍA', '15696'),
(1318, 15, 3, 'SANTANA', '15686'),
(1322, 15, 3, 'SATIVANORTE', '15720'),
(1323, 15, 3, 'SATIVASUR', '15723'),
(1324, 15, 3, 'SIACHOQUE', '15740'),
(1325, 15, 3, 'SOATÁ', '15753'),
(1327, 15, 3, 'SOCHA', '15757'),
(1326, 15, 3, 'SOCOTÁ', '15755'),
(1328, 15, 3, 'SOGAMOSO', '15759'),
(1329, 15, 3, 'SOMONDOCO', '15761'),
(1330, 15, 3, 'SORA', '15762'),
(1332, 15, 3, 'SORACÁ', '15764'),
(1331, 15, 3, 'SOTAQUIRÁ', '15763'),
(1333, 15, 3, 'SUSACÓN', '15774'),
(1334, 15, 3, 'SUTAMARCHÁN', '15776'),
(1335, 15, 3, 'SUTATENZA', '15778'),
(1310, 15, 3, 'SÁCHICA', '15638'),
(1336, 15, 3, 'TASCO', '15790'),
(1337, 15, 3, 'TENZA', '15798'),
(1338, 15, 3, 'TIBANÁ', '15804'),
(1339, 15, 3, 'TIBASOSA', '15806'),
(1340, 15, 3, 'TINJACÁ', '15808'),
(1341, 15, 3, 'TIPACOQUE', '15810'),
(1342, 15, 3, 'TOCA', '15814'),
(1343, 15, 3, 'TOGÜÍ', '15816'),
(1345, 15, 3, 'TOTA', '15822'),
(1231, 15, 3, 'TUNJA', '15001'),
(1346, 15, 3, 'TUNUNGUÁ', '15832'),
(1347, 15, 3, 'TURMEQUÉ', '15835'),
(1348, 15, 3, 'TUTA', '15837'),
(1349, 15, 3, 'TUTAZÁ', '15839'),
(1344, 15, 3, 'TÓPAGA', '15820'),
(1351, 15, 3, 'VENTAQUEMADA', '15861'),
(1281, 15, 3, 'VILLA DE LEYVA', '15407'),
(1352, 15, 3, 'VIRACACHÁ', '15879'),
(1353, 15, 3, 'ZETAQUIRA', '15897'),
(1350, 15, 3, 'ÚMBITA', '15842'),
(1355, 17, 3, 'AGUADAS', '17013'),
(1356, 17, 3, 'ANSERMA', '17042'),
(1357, 17, 3, 'ARANZAZU', '17050'),
(1358, 17, 3, 'BELALCÁZAR', '17088'),
(1359, 17, 3, 'CHINCHINÁ', '17174'),
(1360, 17, 3, 'FILADELFIA', '17272'),
(1361, 17, 3, 'LA DORADA', '17380'),
(1362, 17, 3, 'LA MERCED', '17388'),
(1354, 17, 3, 'MANIZALES', '17001'),
(1363, 17, 3, 'MANZANARES', '17433'),
(1364, 17, 3, 'MARMATO', '17442'),
(1365, 17, 3, 'MARQUETALIA', '17444'),
(1366, 17, 3, 'MARULANDA', '17446'),
(1367, 17, 3, 'NEIRA', '17486'),
(1368, 17, 3, 'NORCASIA', '17495'),
(1370, 17, 3, 'PALESTINA', '17524'),
(1371, 17, 3, 'PENSILVANIA', '17541'),
(1369, 17, 3, 'PÁCORA', '17513'),
(1372, 17, 3, 'RIOSUCIO', '17614'),
(1373, 17, 3, 'RISARALDA', '17616'),
(1374, 17, 3, 'SALAMINA', '17653'),
(1375, 17, 3, 'SAMANÁ', '17662'),
(1376, 17, 3, 'SAN JOSÉ', '17665'),
(1377, 17, 3, 'SUPÍA', '17777'),
(1378, 17, 3, 'VICTORIA', '17867'),
(1379, 17, 3, 'VILLAMARÍA', '17873'),
(1380, 17, 3, 'VITERBO', '17877'),
(1382, 18, 3, 'ALBANIA', '18029'),
(1383, 18, 3, 'BELÉN DE LOS ANDAQUÍES', '18094'),
(1384, 18, 3, 'CARTAGENA DEL CHAIRÁ', '18150'),
(1385, 18, 3, 'CURILLO', '18205'),
(1386, 18, 3, 'EL DONCELLO', '18247'),
(1387, 18, 3, 'EL PAUJÍL', '18256'),
(1381, 18, 3, 'FLORENCIA', '18001'),
(1388, 18, 3, 'LA MONTAÑITA', '18410'),
(1389, 18, 3, 'MILÁN', '18460'),
(1390, 18, 3, 'MORELIA', '18479'),
(1391, 18, 3, 'PUERTO RICO', '18592'),
(1392, 18, 3, 'SAN JOSÉ DEL FRAGUA', '18610'),
(1393, 18, 3, 'SAN VICENTE DEL CAGUÁN', '18753'),
(1394, 18, 3, 'SOLANO', '18756'),
(1395, 18, 3, 'SOLITA', '18785'),
(1396, 18, 3, 'VALPARAÍSO', '18860'),
(1398, 19, 3, 'ALMAGUER', '19022'),
(1399, 19, 3, 'ARGELIA', '19050'),
(1400, 19, 3, 'BALBOA', '19075'),
(1401, 19, 3, 'BOLÍVAR', '19100'),
(1402, 19, 3, 'BUENOS AIRES', '19110'),
(1403, 19, 3, 'CAJIBÍO', '19130'),
(1404, 19, 3, 'CALDONO', '19137'),
(1405, 19, 3, 'CALOTO', '19142'),
(1406, 19, 3, 'CORINTO', '19212'),
(1407, 19, 3, 'EL TAMBO', '19256'),
(1408, 19, 3, 'FLORENCIA', '19290'),
(1409, 19, 3, 'GUACHENÉ', '19300'),
(1410, 19, 3, 'GUAPÍ', '19318'),
(1411, 19, 3, 'INZÁ', '19355'),
(1412, 19, 3, 'JAMBALÓ', '19364'),
(1413, 19, 3, 'LA SIERRA', '19392'),
(1414, 19, 3, 'LA VEGA', '19397'),
(1415, 19, 3, 'LÓPEZ DE MICAY', '19418'),
(1416, 19, 3, 'MERCADERES', '19450'),
(1417, 19, 3, 'MIRANDA', '19455'),
(1418, 19, 3, 'MORALES', '19473'),
(1419, 19, 3, 'PADILLA', '19513'),
(1421, 19, 3, 'PATÍA', '19532'),
(1422, 19, 3, 'PIAMONTE', '19533'),
(1423, 19, 3, 'PIENDAMÓ - TUNÍA', '19548'),
(1397, 19, 3, 'POPAYÁN', '19001'),
(1424, 19, 3, 'PUERTO TEJADA', '19573'),
(1425, 19, 3, 'PURACÉ', '19585'),
(1420, 19, 3, 'PÁEZ', '19517'),
(1426, 19, 3, 'ROSAS', '19622'),
(1427, 19, 3, 'SAN SEBASTIÁN', '19693'),
(1429, 19, 3, 'SANTA ROSA', '19701'),
(1428, 19, 3, 'SANTANDER DE QUILICHAO', '19698'),
(1430, 19, 3, 'SILVIA', '19743'),
(1431, 19, 3, 'SOTARA', '19760'),
(1433, 19, 3, 'SUCRE', '19785'),
(1432, 19, 3, 'SUÁREZ', '19780'),
(1435, 19, 3, 'TIMBIQUÍ', '19809'),
(1434, 19, 3, 'TIMBÍO', '19807'),
(1436, 19, 3, 'TORIBÍO', '19821'),
(1437, 19, 3, 'TOTORÓ', '19824'),
(1438, 19, 3, 'VILLA RICA', '19845'),
(1440, 20, 3, 'AGUACHICA', '20011'),
(1441, 20, 3, 'AGUSTÍN CODAZZI', '20013'),
(1442, 20, 3, 'ASTREA', '20032'),
(1443, 20, 3, 'BECERRIL', '20045'),
(1444, 20, 3, 'BOSCONIA', '20060'),
(1445, 20, 3, 'CHIMICHAGUA', '20175'),
(1446, 20, 3, 'CHIRIGUANÁ', '20178'),
(1447, 20, 3, 'CURUMANÍ', '20228'),
(1448, 20, 3, 'EL COPEY', '20238'),
(1449, 20, 3, 'EL PASO', '20250'),
(1450, 20, 3, 'GAMARRA', '20295'),
(1451, 20, 3, 'GONZÁLEZ', '20310'),
(1452, 20, 3, 'LA GLORIA', '20383'),
(1453, 20, 3, 'LA JAGUA DE IBIRICO', '20400'),
(1459, 20, 3, 'LA PAZ', '20621'),
(1454, 20, 3, 'MANAURE BALCÓN DEL CESAR', '20443'),
(1455, 20, 3, 'PAILITAS', '20517'),
(1456, 20, 3, 'PELAYA', '20550'),
(1457, 20, 3, 'PUEBLO BELLO', '20570'),
(1458, 20, 3, 'RÍO DE ORO', '20614'),
(1460, 20, 3, 'SAN ALBERTO', '20710'),
(1461, 20, 3, 'SAN DIEGO', '20750'),
(1462, 20, 3, 'SAN MARTÍN', '20770'),
(1463, 20, 3, 'TAMALAMEQUE', '20787'),
(1439, 20, 3, 'VALLEDUPAR', '20001'),
(1465, 23, 3, 'AYAPEL', '23068'),
(1466, 23, 3, 'BUENAVISTA', '23079'),
(1467, 23, 3, 'CANALETE', '23090'),
(1468, 23, 3, 'CERETÉ', '23162'),
(1469, 23, 3, 'CHIMÁ', '23168'),
(1470, 23, 3, 'CHINÚ', '23182'),
(1471, 23, 3, 'CIÉNAGA DE ORO', '23189'),
(1472, 23, 3, 'COTORRA', '23300'),
(1473, 23, 3, 'LA APARTADA', '23350'),
(1474, 23, 3, 'LORICA', '23417'),
(1475, 23, 3, 'LOS CÓRDOBAS', '23419'),
(1476, 23, 3, 'MOMIL', '23464'),
(1477, 23, 3, 'MONTELÍBANO', '23466'),
(1464, 23, 3, 'MONTERÍA', '23001'),
(1478, 23, 3, 'MOÑITOS', '23500'),
(1479, 23, 3, 'PLANETA RICA', '23555'),
(1480, 23, 3, 'PUEBLO NUEVO', '23570'),
(1481, 23, 3, 'PUERTO ESCONDIDO', '23574'),
(1482, 23, 3, 'PUERTO LIBERTADOR', '23580'),
(1483, 23, 3, 'PURÍSIMA DE LA CONCEPCIÓN', '23586'),
(1484, 23, 3, 'SAHAGÚN', '23660'),
(1485, 23, 3, 'SAN ANDRÉS DE SOTAVENTO', '23670'),
(1486, 23, 3, 'SAN ANTERO', '23672'),
(1487, 23, 3, 'SAN BERNARDO DEL VIENTO', '23675'),
(1488, 23, 3, 'SAN CARLOS', '23678'),
(1489, 23, 3, 'SAN JOSÉ DE URÉ', '23682'),
(1490, 23, 3, 'SAN PELAYO', '23686'),
(1491, 23, 3, 'TIERRALTA', '23807'),
(1492, 23, 3, 'TUCHÍN', '23815'),
(1493, 23, 3, 'VALENCIA', '23855'),
(1494, 25, 3, 'AGUA DE DIOS', '25001'),
(1495, 25, 3, 'ALBÁN', '25019'),
(1496, 25, 3, 'ANAPOIMA', '25035'),
(1497, 25, 3, 'ANOLAIMA', '25040'),
(1568, 25, 3, 'APULO', '25599'),
(1498, 25, 3, 'ARBELÁEZ', '25053'),
(1499, 25, 3, 'BELTRÁN', '25086'),
(1500, 25, 3, 'BITUIMA', '25095'),
(1501, 25, 3, 'BOJACÁ', '25099'),
(1502, 25, 3, 'CABRERA', '25120'),
(1503, 25, 3, 'CACHIPAY', '25123'),
(1504, 25, 3, 'CAJICÁ', '25126'),
(1505, 25, 3, 'CAPARRAPÍ', '25148'),
(1507, 25, 3, 'CARMEN DE CARUPA', '25154'),
(1508, 25, 3, 'CHAGUANÍ', '25168'),
(1510, 25, 3, 'CHIPAQUE', '25178'),
(1511, 25, 3, 'CHOACHÍ', '25181'),
(1512, 25, 3, 'CHOCONTÁ', '25183'),
(1509, 25, 3, 'CHÍA', '25175'),
(1513, 25, 3, 'COGUA', '25200'),
(1514, 25, 3, 'COTA', '25214'),
(1515, 25, 3, 'CUCUNUBÁ', '25224'),
(1506, 25, 3, 'CÁQUEZA', '25151'),
(1516, 25, 3, 'EL COLEGIO', '25245'),
(1517, 25, 3, 'EL PEÑÓN', '25258'),
(1518, 25, 3, 'EL ROSAL', '25260'),
(1519, 25, 3, 'FACATATIVÁ', '25269'),
(1521, 25, 3, 'FOSCA', '25281'),
(1522, 25, 3, 'FUNZA', '25286'),
(1524, 25, 3, 'FUSAGASUGÁ', '25290'),
(1520, 25, 3, 'FÓMEQUE', '25279'),
(1523, 25, 3, 'FÚQUENE', '25288'),
(1525, 25, 3, 'GACHALÁ', '25293'),
(1526, 25, 3, 'GACHANCIPÁ', '25295'),
(1527, 25, 3, 'GACHETÁ', '25297'),
(1528, 25, 3, 'GAMA', '25299'),
(1529, 25, 3, 'GIRARDOT', '25307'),
(1530, 25, 3, 'GRANADA', '25312'),
(1531, 25, 3, 'GUACHETÁ', '25317'),
(1532, 25, 3, 'GUADUAS', '25320'),
(1533, 25, 3, 'GUASCA', '25322'),
(1534, 25, 3, 'GUATAQUÍ', '25324'),
(1535, 25, 3, 'GUATAVITA', '25326'),
(1536, 25, 3, 'GUAYABAL DE SÍQUIMA', '25328'),
(1537, 25, 3, 'GUAYABETAL', '25335'),
(1538, 25, 3, 'GUTIÉRREZ', '25339'),
(1539, 25, 3, 'JERUSALÉN', '25368'),
(1540, 25, 3, 'JUNÍN', '25372'),
(1541, 25, 3, 'LA CALERA', '25377'),
(1542, 25, 3, 'LA MESA', '25386'),
(1543, 25, 3, 'LA PALMA', '25394'),
(1544, 25, 3, 'LA PEÑA', '25398'),
(1545, 25, 3, 'LA VEGA', '25402'),
(1546, 25, 3, 'LENGUAZAQUE', '25407'),
(1547, 25, 3, 'MACHETÁ', '25426'),
(1548, 25, 3, 'MADRID', '25430'),
(1549, 25, 3, 'MANTA', '25436'),
(1550, 25, 3, 'MEDINA', '25438'),
(1551, 25, 3, 'MOSQUERA', '25473'),
(1552, 25, 3, 'NARIÑO', '25483'),
(1553, 25, 3, 'NEMOCÓN', '25486'),
(1554, 25, 3, 'NILO', '25488'),
(1555, 25, 3, 'NIMAIMA', '25489'),
(1556, 25, 3, 'NOCAIMA', '25491'),
(1558, 25, 3, 'PACHO', '25513'),
(1559, 25, 3, 'PAIME', '25518'),
(1560, 25, 3, 'PANDI', '25524'),
(1561, 25, 3, 'PARATEBUENO', '25530'),
(1562, 25, 3, 'PASCA', '25535'),
(1563, 25, 3, 'PUERTO SALGAR', '25572'),
(1564, 25, 3, 'PULÍ', '25580'),
(1565, 25, 3, 'QUEBRADANEGRA', '25592'),
(1566, 25, 3, 'QUETAME', '25594'),
(1567, 25, 3, 'QUIPILE', '25596'),
(1569, 25, 3, 'RICAURTE', '25612'),
(1570, 25, 3, 'SAN ANTONIO DEL TEQUENDAMA', '25645'),
(1571, 25, 3, 'SAN BERNARDO', '25649'),
(1572, 25, 3, 'SAN CAYETANO', '25653'),
(1573, 25, 3, 'SAN FRANCISCO', '25658'),
(1574, 25, 3, 'SAN JUAN DE RIOSECO', '25662'),
(1575, 25, 3, 'SASAIMA', '25718'),
(1576, 25, 3, 'SESQUILÉ', '25736'),
(1577, 25, 3, 'SIBATÉ', '25740'),
(1578, 25, 3, 'SILVANIA', '25743'),
(1579, 25, 3, 'SIMIJACA', '25745'),
(1580, 25, 3, 'SOACHA', '25754'),
(1581, 25, 3, 'SOPÓ', '25758'),
(1582, 25, 3, 'SUBACHOQUE', '25769'),
(1583, 25, 3, 'SUESCA', '25772'),
(1584, 25, 3, 'SUPATÁ', '25777'),
(1585, 25, 3, 'SUSA', '25779'),
(1586, 25, 3, 'SUTATAUSA', '25781'),
(1587, 25, 3, 'TABIO', '25785'),
(1588, 25, 3, 'TAUSA', '25793'),
(1589, 25, 3, 'TENA', '25797'),
(1590, 25, 3, 'TENJO', '25799'),
(1591, 25, 3, 'TIBACUY', '25805'),
(1592, 25, 3, 'TIBIRITA', '25807'),
(1593, 25, 3, 'TOCAIMA', '25815'),
(1594, 25, 3, 'TOCANCIPÁ', '25817'),
(1595, 25, 3, 'TOPAIPÍ', '25823'),
(1596, 25, 3, 'UBALÁ', '25839'),
(1597, 25, 3, 'UBAQUE', '25841'),
(1599, 25, 3, 'UNE', '25845'),
(1557, 25, 3, 'VENECIA', '25506'),
(1601, 25, 3, 'VERGARA', '25862'),
(1602, 25, 3, 'VIANÍ', '25867'),
(1598, 25, 3, 'VILLA DE SAN DIEGO DE UBATÉ', '25843'),
(1603, 25, 3, 'VILLAGÓMEZ', '25871'),
(1604, 25, 3, 'VILLAPINZÓN', '25873'),
(1605, 25, 3, 'VILLETA', '25875'),
(1606, 25, 3, 'VIOTÁ', '25878'),
(1607, 25, 3, 'YACOPÍ', '25885'),
(1608, 25, 3, 'ZIPACÓN', '25898'),
(1609, 25, 3, 'ZIPAQUIRÁ', '25899'),
(1600, 25, 3, 'ÚTICA', '25851'),
(1611, 27, 3, 'ACANDÍ', '27006'),
(1612, 27, 3, 'ALTO BAUDÓ', '27025'),
(1613, 27, 3, 'ATRATO', '27050'),
(1614, 27, 3, 'BAGADÓ', '27073'),
(1615, 27, 3, 'BAHÍA SOLANO', '27075'),
(1616, 27, 3, 'BAJO BAUDÓ', '27077'),
(1617, 27, 3, 'BOJAYÁ', '27099'),
(1619, 27, 3, 'CARMEN DEL DARIÉN', '27150'),
(1621, 27, 3, 'CONDOTO', '27205'),
(1620, 27, 3, 'CÉRTEGUI', '27160'),
(1618, 27, 3, 'EL CANTÓN DEL SAN PABLO', '27135'),
(1622, 27, 3, 'EL CARMEN DE ATRATO', '27245'),
(1623, 27, 3, 'EL LITORAL DEL SAN JUAN', '27250'),
(1624, 27, 3, 'ISTMINA', '27361'),
(1625, 27, 3, 'JURADÓ', '27372'),
(1626, 27, 3, 'LLORÓ', '27413'),
(1627, 27, 3, 'MEDIO ATRATO', '27425'),
(1628, 27, 3, 'MEDIO BAUDÓ', '27430'),
(1629, 27, 3, 'MEDIO SAN JUAN', '27450'),
(1631, 27, 3, 'NUQUÍ', '27495'),
(1630, 27, 3, 'NÓVITA', '27491'),
(1610, 27, 3, 'QUIBDÓ', '27001'),
(1634, 27, 3, 'RIOSUCIO', '27615'),
(1632, 27, 3, 'RÍO IRÓ', '27580'),
(1633, 27, 3, 'RÍO QUITO', '27600'),
(1635, 27, 3, 'SAN JOSÉ DEL PALMAR', '27660'),
(1636, 27, 3, 'SIPÍ', '27745'),
(1637, 27, 3, 'TADÓ', '27787'),
(1638, 27, 3, 'UNGUÍA', '27800'),
(1639, 27, 3, 'UNIÓN PANAMERICANA', '27810'),
(1641, 41, 3, 'ACEVEDO', '41006'),
(1642, 41, 3, 'AGRADO', '41013'),
(1643, 41, 3, 'AIPE', '41016'),
(1644, 41, 3, 'ALGECIRAS', '41020'),
(1645, 41, 3, 'ALTAMIRA', '41026'),
(1646, 41, 3, 'BARAYA', '41078'),
(1647, 41, 3, 'CAMPOALEGRE', '41132'),
(1648, 41, 3, 'COLOMBIA', '41206'),
(1649, 41, 3, 'ELÍAS', '41244'),
(1650, 41, 3, 'GARZÓN', '41298'),
(1651, 41, 3, 'GIGANTE', '41306'),
(1652, 41, 3, 'GUADALUPE', '41319'),
(1653, 41, 3, 'HOBO', '41349'),
(1655, 41, 3, 'ISNOS', '41359'),
(1656, 41, 3, 'LA ARGENTINA', '41378'),
(1657, 41, 3, 'LA PLATA', '41396'),
(1640, 41, 3, 'NEIVA', '41001'),
(1658, 41, 3, 'NÁTAGA', '41483'),
(1659, 41, 3, 'OPORAPA', '41503'),
(1660, 41, 3, 'PAICOL', '41518'),
(1661, 41, 3, 'PALERMO', '41524'),
(1662, 41, 3, 'PALESTINA', '41530'),
(1663, 41, 3, 'PITAL', '41548'),
(1664, 41, 3, 'PITALITO', '41551'),
(1665, 41, 3, 'RIVERA', '41615'),
(1666, 41, 3, 'SALADOBLANCO', '41660'),
(1667, 41, 3, 'SAN AGUSTÍN', '41668'),
(1668, 41, 3, 'SANTA MARÍA', '41676'),
(1669, 41, 3, 'SUAZA', '41770'),
(1670, 41, 3, 'TARQUI', '41791'),
(1672, 41, 3, 'TELLO', '41799'),
(1673, 41, 3, 'TERUEL', '41801'),
(1671, 41, 3, 'TESALIA', '41797'),
(1674, 41, 3, 'TIMANÁ', '41807'),
(1675, 41, 3, 'VILLAVIEJA', '41872'),
(1676, 41, 3, 'YAGUARÁ', '41885'),
(1654, 41, 3, 'ÍQUIRA', '41357'),
(1678, 44, 3, 'ALBANIA', '44035'),
(1679, 44, 3, 'BARRANCAS', '44078'),
(1680, 44, 3, 'DIBULLA', '44090'),
(1681, 44, 3, 'DISTRACCIÓN', '44098'),
(1682, 44, 3, 'EL MOLINO', '44110'),
(1683, 44, 3, 'FONSECA', '44279'),
(1684, 44, 3, 'HATONUEVO', '44378'),
(1685, 44, 3, 'LA JAGUA DEL PILAR', '44420'),
(1686, 44, 3, 'MAICAO', '44430'),
(1687, 44, 3, 'MANAURE', '44560'),
(1677, 44, 3, 'RIOHACHA', '44001'),
(1688, 44, 3, 'SAN JUAN DEL CESAR', '44650'),
(1689, 44, 3, 'URIBIA', '44847'),
(1690, 44, 3, 'URUMITA', '44855'),
(1691, 44, 3, 'VILLANUEVA', '44874'),
(1693, 47, 3, 'ALGARROBO', '47030'),
(1694, 47, 3, 'ARACATACA', '47053'),
(1695, 47, 3, 'ARIGUANÍ', '47058'),
(1696, 47, 3, 'CERRO DE SAN ANTONIO', '47161'),
(1697, 47, 3, 'CHIVOLO', '47170'),
(1698, 47, 3, 'CIÉNAGA', '47189'),
(1699, 47, 3, 'CONCORDIA', '47205'),
(1700, 47, 3, 'EL BANCO', '47245'),
(1701, 47, 3, 'EL PIÑÓN', '47258'),
(1702, 47, 3, 'EL RETÉN', '47268'),
(1703, 47, 3, 'FUNDACIÓN', '47288'),
(1704, 47, 3, 'GUAMAL', '47318'),
(1705, 47, 3, 'NUEVA GRANADA', '47460'),
(1706, 47, 3, 'PEDRAZA', '47541'),
(1707, 47, 3, 'PIJIÑO DEL CARMEN', '47545'),
(1708, 47, 3, 'PIVIJAY', '47551'),
(1709, 47, 3, 'PLATO', '47555'),
(1710, 47, 3, 'PUEBLOVIEJO', '47570'),
(1711, 47, 3, 'REMOLINO', '47605'),
(1712, 47, 3, 'SABANAS DE SAN ÁNGEL', '47660'),
(1713, 47, 3, 'SALAMINA', '47675'),
(1714, 47, 3, 'SAN SEBASTIÁN DE BUENAVISTA', '47692'),
(1715, 47, 3, 'SAN ZENÓN', '47703'),
(1716, 47, 3, 'SANTA ANA', '47707'),
(1717, 47, 3, 'SANTA BÁRBARA DE PINTO', '47720'),
(1692, 47, 3, 'SANTA MARTA', '47001'),
(1718, 47, 3, 'SITIONUEVO', '47745'),
(1719, 47, 3, 'TENERIFE', '47798'),
(1720, 47, 3, 'ZAPAYÁN', '47960'),
(1721, 47, 3, 'ZONA BANANERA', '47980'),
(1723, 50, 3, 'ACACÍAS', '50006'),
(1724, 50, 3, 'BARRANCA DE UPÍA', '50110'),
(1725, 50, 3, 'CABUYARO', '50124'),
(1726, 50, 3, 'CASTILLA LA NUEVA', '50150'),
(1727, 50, 3, 'CUBARRAL', '50223'),
(1728, 50, 3, 'CUMARAL', '50226'),
(1729, 50, 3, 'EL CALVARIO', '50245'),
(1730, 50, 3, 'EL CASTILLO', '50251'),
(1731, 50, 3, 'EL DORADO', '50270'),
(1732, 50, 3, 'FUENTE DE ORO', '50287'),
(1733, 50, 3, 'GRANADA', '50313'),
(1734, 50, 3, 'GUAMAL', '50318'),
(1737, 50, 3, 'LA MACARENA', '50350'),
(1739, 50, 3, 'LEJANÍAS', '50400'),
(1735, 50, 3, 'MAPIRIPÁN', '50325'),
(1736, 50, 3, 'MESETAS', '50330'),
(1740, 50, 3, 'PUERTO CONCORDIA', '50450'),
(1741, 50, 3, 'PUERTO GAITÁN', '50568'),
(1743, 50, 3, 'PUERTO LLERAS', '50577'),
(1742, 50, 3, 'PUERTO LÓPEZ', '50573'),
(1744, 50, 3, 'PUERTO RICO', '50590'),
(1745, 50, 3, 'RESTREPO', '50606'),
(1746, 50, 3, 'SAN CARLOS DE GUAROA', '50680'),
(1747, 50, 3, 'SAN JUAN DE ARAMA', '50683'),
(1748, 50, 3, 'SAN JUANITO', '50686'),
(1749, 50, 3, 'SAN MARTÍN', '50689'),
(1738, 50, 3, 'URIBE', '50370'),
(1722, 50, 3, 'VILLAVICENCIO', '50001'),
(1750, 50, 3, 'VISTAHERMOSA', '50711'),
(1752, 52, 3, 'ALBÁN', '52019'),
(1753, 52, 3, 'ALDANA', '52022'),
(1754, 52, 3, 'ANCUYÁ', '52036'),
(1755, 52, 3, 'ARBOLEDA', '52051'),
(1756, 52, 3, 'BARBACOAS', '52079'),
(1757, 52, 3, 'BELÉN', '52083'),
(1758, 52, 3, 'BUESACO', '52110'),
(1766, 52, 3, 'CHACHAGÜÍ', '52240'),
(1759, 52, 3, 'COLÓN', '52203'),
(1760, 52, 3, 'CONSACÁ', '52207'),
(1761, 52, 3, 'CONTADERO', '52210'),
(1763, 52, 3, 'CUASPÚD', '52224'),
(1764, 52, 3, 'CUMBAL', '52227'),
(1765, 52, 3, 'CUMBITARA', '52233'),
(1762, 52, 3, 'CÓRDOBA', '52215'),
(1767, 52, 3, 'EL CHARCO', '52250'),
(1768, 52, 3, 'EL PEÑOL', '52254'),
(1769, 52, 3, 'EL ROSARIO', '52256'),
(1770, 52, 3, 'EL TABLÓN DE GÓMEZ', '52258'),
(1771, 52, 3, 'EL TAMBO', '52260'),
(1793, 52, 3, 'FRANCISCO PIZARRO', '52520'),
(1772, 52, 3, 'FUNES', '52287'),
(1773, 52, 3, 'GUACHUCAL', '52317'),
(1774, 52, 3, 'GUAITARILLA', '52320'),
(1775, 52, 3, 'GUALMATÁN', '52323'),
(1776, 52, 3, 'ILES', '52352'),
(1777, 52, 3, 'IMUÉS', '52354'),
(1778, 52, 3, 'IPIALES', '52356'),
(1779, 52, 3, 'LA CRUZ', '52378'),
(1780, 52, 3, 'LA FLORIDA', '52381'),
(1781, 52, 3, 'LA LLANADA', '52385'),
(1782, 52, 3, 'LA TOLA', '52390'),
(1783, 52, 3, 'LA UNIÓN', '52399'),
(1784, 52, 3, 'LEIVA', '52405'),
(1785, 52, 3, 'LINARES', '52411'),
(1786, 52, 3, 'LOS ANDES', '52418'),
(1787, 52, 3, 'MAGÜÍ', '52427'),
(1788, 52, 3, 'MALLAMA', '52435'),
(1789, 52, 3, 'MOSQUERA', '52473'),
(1790, 52, 3, 'NARIÑO', '52480'),
(1791, 52, 3, 'OLAYA HERRERA', '52490'),
(1792, 52, 3, 'OSPINA', '52506'),
(1751, 52, 3, 'PASTO', '52001'),
(1794, 52, 3, 'POLICARPA', '52540'),
(1795, 52, 3, 'POTOSÍ', '52560'),
(1796, 52, 3, 'PROVIDENCIA', '52565'),
(1797, 52, 3, 'PUERRES', '52573'),
(1798, 52, 3, 'PUPIALES', '52585'),
(1799, 52, 3, 'RICAURTE', '52612'),
(1800, 52, 3, 'ROBERTO PAYÁN', '52621'),
(1801, 52, 3, 'SAMANIEGO', '52678'),
(1812, 52, 3, 'SAN ANDRÉS DE TUMACO', '52835'),
(1803, 52, 3, 'SAN BERNARDO', '52685'),
(1804, 52, 3, 'SAN LORENZO', '52687'),
(1805, 52, 3, 'SAN PABLO', '52693'),
(1806, 52, 3, 'SAN PEDRO DE CARTAGO', '52694'),
(1802, 52, 3, 'SANDONÁ', '52683'),
(1807, 52, 3, 'SANTA BÁRBARA', '52696'),
(1808, 52, 3, 'SANTACRUZ', '52699'),
(1809, 52, 3, 'SAPUYES', '52720'),
(1810, 52, 3, 'TAMINANGO', '52786'),
(1811, 52, 3, 'TANGUA', '52788'),
(1813, 52, 3, 'TÚQUERRES', '52838'),
(1814, 52, 3, 'YACUANQUER', '52885'),
(1817, 54, 3, 'ARBOLEDAS', '54051'),
(1818, 54, 3, 'BOCHALEMA', '54099'),
(1819, 54, 3, 'BUCARASICA', '54109'),
(1822, 54, 3, 'CHINÁCOTA', '54172'),
(1823, 54, 3, 'CHITAGÁ', '54174'),
(1824, 54, 3, 'CONVENCIÓN', '54206'),
(1825, 54, 3, 'CUCUTILLA', '54223'),
(1821, 54, 3, 'CÁCHIRA', '54128'),
(1820, 54, 3, 'CÁCOTA', '54125'),
(1826, 54, 3, 'DURANIA', '54239'),
(1827, 54, 3, 'EL CARMEN', '54245'),
(1828, 54, 3, 'EL TARRA', '54250'),
(1829, 54, 3, 'EL ZULIA', '54261'),
(1830, 54, 3, 'GRAMALOTE', '54313'),
(1831, 54, 3, 'HACARÍ', '54344'),
(1832, 54, 3, 'HERRÁN', '54347'),
(1834, 54, 3, 'LA ESPERANZA', '54385'),
(1835, 54, 3, 'LA PLAYA', '54398'),
(1833, 54, 3, 'LABATECA', '54377'),
(1836, 54, 3, 'LOS PATIOS', '54405'),
(1837, 54, 3, 'LOURDES', '54418'),
(1838, 54, 3, 'MUTISCUA', '54480'),
(1839, 54, 3, 'OCAÑA', '54498'),
(1840, 54, 3, 'PAMPLONA', '54518'),
(1841, 54, 3, 'PAMPLONITA', '54520'),
(1842, 54, 3, 'PUERTO SANTANDER', '54553'),
(1843, 54, 3, 'RAGONVALIA', '54599'),
(1844, 54, 3, 'SALAZAR', '54660'),
(1845, 54, 3, 'SAN CALIXTO', '54670'),
(1846, 54, 3, 'SAN CAYETANO', '54673'),
(1815, 54, 3, 'SAN JOSÉ DE CÚCUTA', '54001'),
(1847, 54, 3, 'SANTIAGO', '54680'),
(1848, 54, 3, 'SARDINATA', '54720'),
(1849, 54, 3, 'SILOS', '54743'),
(1850, 54, 3, 'TEORAMA', '54800'),
(1851, 54, 3, 'TIBÚ', '54810'),
(1852, 54, 3, 'TOLEDO', '54820'),
(1853, 54, 3, 'VILLA CARO', '54871'),
(1854, 54, 3, 'VILLA DEL ROSARIO', '54874'),
(1816, 54, 3, 'ÁBREGO', '54003'),
(1855, 63, 3, 'ARMENIA', '63001'),
(1856, 63, 3, 'BUENAVISTA', '63111'),
(1857, 63, 3, 'CALARCÁ', '63130'),
(1858, 63, 3, 'CIRCASIA', '63190'),
(1859, 63, 3, 'CÓRDOBA', '63212'),
(1860, 63, 3, 'FILANDIA', '63272'),
(1861, 63, 3, 'GÉNOVA', '63302'),
(1862, 63, 3, 'LA TEBAIDA', '63401'),
(1863, 63, 3, 'MONTENEGRO', '63470'),
(1864, 63, 3, 'PIJAO', '63548'),
(1865, 63, 3, 'QUIMBAYA', '63594'),
(1866, 63, 3, 'SALENTO', '63690'),
(1868, 66, 3, 'APÍA', '66045'),
(1869, 66, 3, 'BALBOA', '66075'),
(1870, 66, 3, 'BELÉN DE UMBRÍA', '66088'),
(1871, 66, 3, 'DOSQUEBRADAS', '66170'),
(1872, 66, 3, 'GUÁTICA', '66318'),
(1873, 66, 3, 'LA CELIA', '66383'),
(1874, 66, 3, 'LA VIRGINIA', '66400'),
(1875, 66, 3, 'MARSELLA', '66440'),
(1876, 66, 3, 'MISTRATÓ', '66456'),
(1867, 66, 3, 'PEREIRA', '66001'),
(1877, 66, 3, 'PUEBLO RICO', '66572'),
(1878, 66, 3, 'QUINCHÍA', '66594'),
(1879, 66, 3, 'SANTA ROSA DE CABAL', '66682'),
(1880, 66, 3, 'SANTUARIO', '66687'),
(1882, 68, 3, 'AGUADA', '68013'),
(1883, 68, 3, 'ALBANIA', '68020'),
(1884, 68, 3, 'ARATOCA', '68051'),
(1885, 68, 3, 'BARBOSA', '68077'),
(1886, 68, 3, 'BARICHARA', '68079'),
(1887, 68, 3, 'BARRANCABERMEJA', '68081'),
(1888, 68, 3, 'BETULIA', '68092'),
(1889, 68, 3, 'BOLÍVAR', '68101'),
(1881, 68, 3, 'BUCARAMANGA', '68001'),
(1890, 68, 3, 'CABRERA', '68121'),
(1891, 68, 3, 'CALIFORNIA', '68132'),
(1892, 68, 3, 'CAPITANEJO', '68147'),
(1893, 68, 3, 'CARCASÍ', '68152'),
(1894, 68, 3, 'CEPITÁ', '68160'),
(1895, 68, 3, 'CERRITO', '68162'),
(1896, 68, 3, 'CHARALÁ', '68167'),
(1897, 68, 3, 'CHARTA', '68169'),
(1898, 68, 3, 'CHIMA', '68176'),
(1899, 68, 3, 'CHIPATÁ', '68179'),
(1900, 68, 3, 'CIMITARRA', '68190'),
(1901, 68, 3, 'CONCEPCIÓN', '68207'),
(1902, 68, 3, 'CONFINES', '68209'),
(1903, 68, 3, 'CONTRATACIÓN', '68211'),
(1904, 68, 3, 'COROMORO', '68217'),
(1905, 68, 3, 'CURITÍ', '68229'),
(1906, 68, 3, 'EL CARMEN DE CHUCURÍ', '68235'),
(1907, 68, 3, 'EL GUACAMAYO', '68245'),
(1908, 68, 3, 'EL PEÑÓN', '68250'),
(1909, 68, 3, 'EL PLAYÓN', '68255'),
(1910, 68, 3, 'ENCINO', '68264'),
(1911, 68, 3, 'ENCISO', '68266'),
(1913, 68, 3, 'FLORIDABLANCA', '68276'),
(1912, 68, 3, 'FLORIÁN', '68271'),
(1914, 68, 3, 'GALÁN', '68296'),
(1916, 68, 3, 'GIRÓN', '68307'),
(1917, 68, 3, 'GUACA', '68318'),
(1918, 68, 3, 'GUADALUPE', '68320'),
(1919, 68, 3, 'GUAPOTÁ', '68322'),
(1920, 68, 3, 'GUAVATÁ', '68324'),
(1915, 68, 3, 'GÁMBITA', '68298'),
(1921, 68, 3, 'GÜEPSA', '68327'),
(1922, 68, 3, 'HATO', '68344'),
(1923, 68, 3, 'JESÚS MARÍA', '68368'),
(1924, 68, 3, 'JORDÁN', '68370'),
(1925, 68, 3, 'LA BELLEZA', '68377'),
(1927, 68, 3, 'LA PAZ', '68397'),
(1926, 68, 3, 'LANDÁZURI', '68385'),
(1928, 68, 3, 'LEBRIJA', '68406'),
(1929, 68, 3, 'LOS SANTOS', '68418'),
(1930, 68, 3, 'MACARAVITA', '68425'),
(1932, 68, 3, 'MATANZA', '68444'),
(1933, 68, 3, 'MOGOTES', '68464'),
(1934, 68, 3, 'MOLAGAVITA', '68468'),
(1931, 68, 3, 'MÁLAGA', '68432'),
(1935, 68, 3, 'OCAMONTE', '68498'),
(1936, 68, 3, 'OIBA', '68500'),
(1937, 68, 3, 'ONZAGA', '68502'),
(1938, 68, 3, 'PALMAR', '68522'),
(1939, 68, 3, 'PALMAS DEL SOCORRO', '68524'),
(1941, 68, 3, 'PIEDECUESTA', '68547'),
(1942, 68, 3, 'PINCHOTE', '68549'),
(1943, 68, 3, 'PUENTE NACIONAL', '68572'),
(1944, 68, 3, 'PUERTO PARRA', '68573'),
(1945, 68, 3, 'PUERTO WILCHES', '68575'),
(1940, 68, 3, 'PÁRAMO', '68533'),
(1946, 68, 3, 'RIONEGRO', '68615'),
(1947, 68, 3, 'SABANA DE TORRES', '68655'),
(1948, 68, 3, 'SAN ANDRÉS', '68669'),
(1949, 68, 3, 'SAN BENITO', '68673'),
(1950, 68, 3, 'SAN GIL', '68679'),
(1951, 68, 3, 'SAN JOAQUÍN', '68682'),
(1952, 68, 3, 'SAN JOSÉ DE MIRANDA', '68684'),
(1953, 68, 3, 'SAN MIGUEL', '68686'),
(1954, 68, 3, 'SAN VICENTE DE CHUCURÍ', '68689'),
(1955, 68, 3, 'SANTA BÁRBARA', '68705'),
(1956, 68, 3, 'SANTA HELENA DEL OPÓN', '68720'),
(1957, 68, 3, 'SIMACOTA', '68745'),
(1958, 68, 3, 'SOCORRO', '68755'),
(1959, 68, 3, 'SUAITA', '68770'),
(1960, 68, 3, 'SUCRE', '68773'),
(1961, 68, 3, 'SURATÁ', '68780'),
(1962, 68, 3, 'TONA', '68820'),
(1963, 68, 3, 'VALLE DE SAN JOSÉ', '68855'),
(1965, 68, 3, 'VETAS', '68867'),
(1966, 68, 3, 'VILLANUEVA', '68872'),
(1964, 68, 3, 'VÉLEZ', '68861'),
(1967, 68, 3, 'ZAPATOCA', '68895'),
(1969, 70, 3, 'BUENAVISTA', '70110'),
(1970, 70, 3, 'CAIMITO', '70124'),
(1974, 70, 3, 'CHALÁN', '70230'),
(1971, 70, 3, 'COLOSÓ', '70204'),
(1972, 70, 3, 'COROZAL', '70215'),
(1973, 70, 3, 'COVEÑAS', '70221'),
(1975, 70, 3, 'EL ROBLE', '70233'),
(1976, 70, 3, 'GALERAS', '70235'),
(1977, 70, 3, 'GUARANDA', '70265'),
(1978, 70, 3, 'LA UNIÓN', '70400'),
(1979, 70, 3, 'LOS PALMITOS', '70418'),
(1980, 70, 3, 'MAJAGUAL', '70429'),
(1981, 70, 3, 'MORROA', '70473'),
(1982, 70, 3, 'OVEJAS', '70508'),
(1983, 70, 3, 'PALMITO', '70523'),
(1984, 70, 3, 'SAMPUÉS', '70670'),
(1985, 70, 3, 'SAN BENITO ABAD', '70678'),
(1986, 70, 3, 'SAN JUAN DE BETULIA', '70702'),
(1990, 70, 3, 'SAN LUIS DE SINCÉ', '70742'),
(1987, 70, 3, 'SAN MARCOS', '70708'),
(1988, 70, 3, 'SAN ONOFRE', '70713'),
(1989, 70, 3, 'SAN PEDRO', '70717'),
(1992, 70, 3, 'SANTIAGO DE TOLÚ', '70820'),
(1968, 70, 3, 'SINCELEJO', '70001'),
(1991, 70, 3, 'SUCRE', '70771'),
(1993, 70, 3, 'TOLÚ VIEJO', '70823'),
(1995, 73, 3, 'ALPUJARRA', '73024'),
(1996, 73, 3, 'ALVARADO', '73026'),
(1997, 73, 3, 'AMBALEMA', '73030'),
(1998, 73, 3, 'ANZOÁTEGUI', '73043'),
(1999, 73, 3, 'ARMERO', '73055'),
(2000, 73, 3, 'ATACO', '73067'),
(2001, 73, 3, 'CAJAMARCA', '73124'),
(2002, 73, 3, 'CARMEN DE APICALÁ', '73148'),
(2003, 73, 3, 'CASABIANCA', '73152'),
(2004, 73, 3, 'CHAPARRAL', '73168'),
(2005, 73, 3, 'COELLO', '73200'),
(2006, 73, 3, 'COYAIMA', '73217'),
(2007, 73, 3, 'CUNDAY', '73226'),
(2008, 73, 3, 'DOLORES', '73236'),
(2009, 73, 3, 'ESPINAL', '73268'),
(2010, 73, 3, 'FALAN', '73270'),
(2011, 73, 3, 'FLANDES', '73275'),
(2012, 73, 3, 'FRESNO', '73283'),
(2013, 73, 3, 'GUAMO', '73319'),
(2014, 73, 3, 'HERVEO', '73347'),
(2015, 73, 3, 'HONDA', '73349'),
(1994, 73, 3, 'IBAGUÉ', '73001'),
(2016, 73, 3, 'ICONONZO', '73352'),
(2017, 73, 3, 'LÉRIDA', '73408'),
(2018, 73, 3, 'LÍBANO', '73411'),
(2020, 73, 3, 'MELGAR', '73449'),
(2021, 73, 3, 'MURILLO', '73461'),
(2022, 73, 3, 'NATAGAIMA', '73483'),
(2023, 73, 3, 'ORTEGA', '73504'),
(2024, 73, 3, 'PALOCABILDO', '73520'),
(2025, 73, 3, 'PIEDRAS', '73547'),
(2026, 73, 3, 'PLANADAS', '73555'),
(2027, 73, 3, 'PRADO', '73563'),
(2028, 73, 3, 'PURIFICACIÓN', '73585'),
(2029, 73, 3, 'RIOBLANCO', '73616'),
(2030, 73, 3, 'RONCESVALLES', '73622'),
(2031, 73, 3, 'ROVIRA', '73624'),
(2032, 73, 3, 'SALDAÑA', '73671'),
(2033, 73, 3, 'SAN ANTONIO', '73675'),
(2034, 73, 3, 'SAN LUIS', '73678'),
(2019, 73, 3, 'SAN SEBASTIÁN DE MARIQUITA', '73443'),
(2035, 73, 3, 'SANTA ISABEL', '73686'),
(2036, 73, 3, 'SUÁREZ', '73770'),
(2037, 73, 3, 'VALLE DE SAN JUAN', '73854'),
(2038, 73, 3, 'VENADILLO', '73861'),
(2039, 73, 3, 'VILLAHERMOSA', '73870'),
(2040, 73, 3, 'VILLARRICA', '73873'),
(2042, 76, 3, 'ALCALÁ', '76020'),
(2043, 76, 3, 'ANDALUCÍA', '76036'),
(2044, 76, 3, 'ANSERMANUEVO', '76041'),
(2045, 76, 3, 'ARGELIA', '76054'),
(2046, 76, 3, 'BOLÍVAR', '76100'),
(2047, 76, 3, 'BUENAVENTURA', '76109'),
(2049, 76, 3, 'BUGALAGRANDE', '76113'),
(2050, 76, 3, 'CAICEDONIA', '76122'),
(2041, 76, 3, 'CALI', '76001'),
(2051, 76, 3, 'CALIMA', '76126'),
(2052, 76, 3, 'CANDELARIA', '76130'),
(2053, 76, 3, 'CARTAGO', '76147'),
(2054, 76, 3, 'DAGUA', '76233'),
(2056, 76, 3, 'EL CAIRO', '76246'),
(2057, 76, 3, 'EL CERRITO', '76248'),
(2058, 76, 3, 'EL DOVIO', '76250'),
(2055, 76, 3, 'EL ÁGUILA', '76243'),
(2059, 76, 3, 'FLORIDA', '76275'),
(2060, 76, 3, 'GINEBRA', '76306'),
(2061, 76, 3, 'GUACARÍ', '76318'),
(2048, 76, 3, 'GUADALAJARA DE BUGA', '76111'),
(2062, 76, 3, 'JAMUNDÍ', '76364'),
(2063, 76, 3, 'LA CUMBRE', '76377'),
(2064, 76, 3, 'LA UNIÓN', '76400'),
(2065, 76, 3, 'LA VICTORIA', '76403'),
(2066, 76, 3, 'OBANDO', '76497'),
(2067, 76, 3, 'PALMIRA', '76520'),
(2068, 76, 3, 'PRADERA', '76563'),
(2069, 76, 3, 'RESTREPO', '76606'),
(2070, 76, 3, 'RIOFRÍO', '76616'),
(2071, 76, 3, 'ROLDANILLO', '76622'),
(2072, 76, 3, 'SAN PEDRO', '76670'),
(2073, 76, 3, 'SEVILLA', '76736'),
(2074, 76, 3, 'TORO', '76823'),
(2075, 76, 3, 'TRUJILLO', '76828'),
(2076, 76, 3, 'TULUÁ', '76834'),
(2077, 76, 3, 'ULLOA', '76845'),
(2078, 76, 3, 'VERSALLES', '76863'),
(2079, 76, 3, 'VIJES', '76869'),
(2080, 76, 3, 'YOTOCO', '76890'),
(2081, 76, 3, 'YUMBO', '76892'),
(2082, 76, 3, 'ZARZAL', '76895'),
(2083, 81, 3, 'ARAUCA', '81001'),
(2084, 81, 3, 'ARAUQUITA', '81065'),
(2085, 81, 3, 'CRAVO NORTE', '81220'),
(2086, 81, 3, 'FORTUL', '81300'),
(2087, 81, 3, 'PUERTO RONDÓN', '81591'),
(2088, 81, 3, 'SARAVENA', '81736'),
(2089, 81, 3, 'TAME', '81794'),
(2091, 85, 3, 'AGUAZUL', '85010'),
(2092, 85, 3, 'CHÁMEZA', '85015'),
(2093, 85, 3, 'HATO COROZAL', '85125'),
(2094, 85, 3, 'LA SALINA', '85136'),
(2095, 85, 3, 'MANÍ', '85139'),
(2096, 85, 3, 'MONTERREY', '85162'),
(2097, 85, 3, 'NUNCHÍA', '85225'),
(2098, 85, 3, 'OROCUÉ', '85230'),
(2099, 85, 3, 'PAZ DE ARIPORO', '85250'),
(2100, 85, 3, 'PORE', '85263'),
(2101, 85, 3, 'RECETOR', '85279'),
(2102, 85, 3, 'SABANALARGA', '85300'),
(2104, 85, 3, 'SAN LUIS DE PALENQUE', '85325'),
(2103, 85, 3, 'SÁCAMA', '85315'),
(2106, 85, 3, 'TAURAMENA', '85410'),
(2107, 85, 3, 'TRINIDAD', '85430'),
(2105, 85, 3, 'TÁMARA', '85400'),
(2108, 85, 3, 'VILLANUEVA', '85440'),
(2090, 85, 3, 'YOPAL', '85001'),
(2110, 86, 3, 'COLÓN', '86219'),
(2109, 86, 3, 'MOCOA', '86001'),
(2111, 86, 3, 'ORITO', '86320'),
(2112, 86, 3, 'PUERTO ASÍS', '86568'),
(2113, 86, 3, 'PUERTO CAICEDO', '86569'),
(2114, 86, 3, 'PUERTO GUZMÁN', '86571'),
(2115, 86, 3, 'PUERTO LEGUÍZAMO', '86573'),
(2117, 86, 3, 'SAN FRANCISCO', '86755'),
(2118, 86, 3, 'SAN MIGUEL', '86757'),
(2119, 86, 3, 'SANTIAGO', '86760'),
(2116, 86, 3, 'SIBUNDOY', '86749'),
(2120, 86, 3, 'VALLE DEL GUAMUEZ', '86865'),
(2121, 86, 3, 'VILLAGARZÓN', '86885'),
(2123, 88, 3, 'PROVIDENCIA', '88564'),
(2122, 88, 3, 'SAN ANDRÉS', '88001'),
(2125, 91, 3, 'EL ENCANTO', '91263'),
(2126, 91, 3, 'LA CHORRERA', '91405'),
(2127, 91, 3, 'LA PEDRERA', '91407'),
(2128, 91, 3, 'LA VICTORIA', '91430'),
(2124, 91, 3, 'LETICIA', '91001'),
(2129, 91, 3, 'MIRITÍ - PARANÁ', '91460'),
(2130, 91, 3, 'PUERTO ALEGRÍA', '91530'),
(2131, 91, 3, 'PUERTO ARICA', '91536'),
(2132, 91, 3, 'PUERTO NARIÑO', '91540'),
(2133, 91, 3, 'PUERTO SANTANDER', '91669'),
(2134, 91, 3, 'TARAPACÁ', '91798'),
(2136, 94, 3, 'BARRANCO MINAS', '94343'),
(2141, 94, 3, 'CACAHUAL', '94886'),
(2135, 94, 3, 'INÍRIDA', '94001'),
(2140, 94, 3, 'LA GUADALUPE', '94885'),
(2137, 94, 3, 'MAPIRIPANA', '94663'),
(2143, 94, 3, 'MORICHAL', '94888'),
(2142, 94, 3, 'PANA PANA', '94887'),
(2139, 94, 3, 'PUERTO COLOMBIA', '94884'),
(2138, 94, 3, 'SAN FELIPE', '94883'),
(2145, 95, 3, 'CALAMAR', '95015'),
(2146, 95, 3, 'EL RETORNO', '95025'),
(2147, 95, 3, 'MIRAFLORES', '95200'),
(2144, 95, 3, 'SAN JOSÉ DEL GUAVIARE', '95001'),
(2149, 97, 3, 'CARURÚ', '97161'),
(2148, 97, 3, 'MITÚ', '97001'),
(2150, 97, 3, 'PACOA', '97511'),
(2152, 97, 3, 'PAPUNAHUA', '97777'),
(2151, 97, 3, 'TARAIRA', '97666'),
(2153, 97, 3, 'YAVARATÉ', '97889'),
(2157, 99, 3, 'CUMARIBO', '99773'),
(2155, 99, 3, 'LA PRIMAVERA', '99524'),
(2154, 99, 3, 'PUERTO CARREÑO', '99001'),
(2156, 99, 3, 'SANTA ROSALÍA', '99624');


-- =============================================
-- USUARIO ADMINISTRADOR POR DEFECTO
-- Password: admin123 (bcrypt hash)
-- =============================================
INSERT INTO users (id, name, email, password, created_at, updated_at) VALUES
(1, 'Administrador', 'admin@testimonios.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

INSERT INTO esclarecimiento.entrevistador (id_entrevistador, id_usuario, id_nivel, solo_lectura, compromiso_reserva) VALUES
(1, 1, 1, 0, 1);

-- Resetear secuencias
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('esclarecimiento.entrevistador_id_entrevistador_seq', (SELECT MAX(id_entrevistador) FROM esclarecimiento.entrevistador));
SELECT setval('catalogos.cat_cat_id_cat_seq', (SELECT MAX(id_cat) FROM catalogos.cat_cat));
SELECT setval('catalogos.cat_item_id_item_seq', (SELECT MAX(id_item) FROM catalogos.cat_item));
SELECT setval('catalogos.geo_id_geo_seq', (SELECT MAX(id_geo) FROM catalogos.geo));
SELECT setval('catalogos.criterio_fijo_id_opcion_seq', (SELECT MAX(id_opcion) FROM catalogos.criterio_fijo));

-- =============================================
-- FIN DEL SCRIPT
-- =============================================
