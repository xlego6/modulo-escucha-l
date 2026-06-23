# Diccionario de Datos — Módulo Escucha Lite

> Generado automáticamente con `php artisan dic:generar` el 2026-06-10 15:10.
> No editar a mano: las descripciones se mantienen como `COMMENT ON` en la BD.

**Motor:** PostgreSQL 11.22 on x86_64-pc-linux-musl, compiled by gcc (Alpine 13.2.1_git20231014) 13.2.1 20231014, 64-bit  
**Base de datos:** `testimonios`  
**Esquemas:** `public`, `esclarecimiento`, `fichas`, `catalogos`

---

## Índice de tablas

| # | Esquema | Tabla | Descripción |
|---|---------|-------|-------------|
| 1 | catalogos | cat_cat | Catálogos maestros |
| 2 | catalogos | cat_item | Ítems de catálogo |
| 3 | catalogos | criterio_fijo | Opciones fijas del sistema |
| 4 | catalogos | geo | Geografía jerárquica |
| 5 | esclarecimiento | adjunto | Archivos adjuntos |
| 6 | esclarecimiento | asignacion_anonimizacion | Asignaciones de anonimización |
| 7 | esclarecimiento | asignacion_transcripcion | Asignaciones de transcripción |
| 8 | esclarecimiento | compromiso_firma | Firmas de compromisos |
| 9 | esclarecimiento | contenido_discapacidad | Pivot: condiciones de discapacidad de las víctimas del expediente |
| 10 | esclarecimiento | contenido_etnia | Pivot: grupos étnicos de las víctimas del expediente |
| 11 | esclarecimiento | contenido_hecho_victimizante | Pivot: hechos victimizantes narrados en el expediente |
| 12 | esclarecimiento | contenido_identidad_genero | Pivot: identidades de género de las víctimas del expediente |
| 13 | esclarecimiento | contenido_lugar | Pivot: lugares (departamento/municipio) asociados a los hechos del expediente |
| 14 | esclarecimiento | contenido_ocupacion | Pivot: ocupaciones de las víctimas del expediente |
| 15 | esclarecimiento | contenido_orientacion_sexual | Pivot: orientaciones sexuales de las víctimas del expediente |
| 16 | esclarecimiento | contenido_poblacion | Pivot: poblaciones de especial protección del expediente |
| 17 | esclarecimiento | contenido_practica_resistencia | Pivot: prácticas de resistencia narradas en el expediente |
| 18 | esclarecimiento | contenido_rango_etario | Pivot: rangos etarios de las víctimas del expediente |
| 19 | esclarecimiento | contenido_responsable | Pivot: actores responsables señalados en el expediente |
| 20 | esclarecimiento | contenido_sexo | Pivot: sexo de las víctimas del expediente |
| 21 | esclarecimiento | contenido_testimonio | Contenido analítico |
| 22 | esclarecimiento | e_ind_fvt | Entrevistas / Expedientes |
| 23 | esclarecimiento | entidad_detectada | Entidades NER |
| 24 | esclarecimiento | entrevista_area_compatible | Pivot: áreas de la CEV compatibles con el expediente |
| 25 | esclarecimiento | entrevista_formato | Pivot: formatos de entrega del expediente |
| 26 | esclarecimiento | entrevista_idioma | Pivot: idiomas presentes en la entrevista |
| 27 | esclarecimiento | entrevista_modalidad | Pivot: modalidades de toma de la entrevista |
| 28 | esclarecimiento | entrevista_necesidad_reparacion | Pivot: necesidades de reparación identificadas en el expediente |
| 29 | esclarecimiento | entrevistador | Perfil operativo del usuario |
| 30 | esclarecimiento | failed_jobs | Trabajos fallidos de la cola de Laravel |
| 31 | esclarecimiento | importacion_expedientes | Expedientes de importación |
| 32 | esclarecimiento | importaciones_masivas | Lotes de importación |
| 33 | esclarecimiento | jobs | Cola de trabajos asíncronos de Laravel |
| 34 | esclarecimiento | permiso | Permisos de acceso |
| 35 | esclarecimiento | rol | Roles de control de acceso |
| 36 | esclarecimiento | rol_modulo_permiso | Permisos por rol y módulo |
| 37 | esclarecimiento | trabajo_procesamiento | Trabajos automáticos |
| 38 | fichas | consentimiento_informado | Consentimiento por persona |
| 39 | fichas | entrevista | Consentimiento e información de la entrevista |
| 40 | fichas | persona | Datos personales |
| 41 | fichas | persona_entrevistada | Relación persona ↔ entrevista |
| 42 | fichas | persona_ocupacion | Pivot: ocupaciones adicionales de una persona |
| 43 | fichas | persona_poblacion | Pivot: poblaciones de especial protección de una persona |
| 44 | public | migrations | Historial de migraciones aplicadas por Laravel |
| 45 | public | traza_actividad | Auditoría del sistema |
| 46 | public | users | Usuarios del sistema |

---

## 1. `catalogos.cat_cat` — Catálogos maestros

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_cat | INTEGER | No | PK |  |
| nombre | CHARACTER VARYING(100) | No |  |  |
| descripcion | CHARACTER VARYING(255) | Sí |  |  |
| editable | INTEGER | Sí |  |  |
| id_reclasificado | INTEGER | Sí | FK → catalogos.cat_cat |  |

---

## 2. `catalogos.cat_item` — Ítems de catálogo

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_item | INTEGER | No | PK | Identificador del ítem |
| id_cat | INTEGER | No | FK → catalogos.cat_cat |  |
| descripcion | CHARACTER VARYING(255) | No |  |  |
| abreviado | CHARACTER VARYING(50) | Sí |  |  |
| texto | TEXT | Sí |  |  |
| orden | INTEGER | Sí |  |  |
| predeterminado | INTEGER | Sí |  |  |
| otro | CHARACTER VARYING(255) | Sí |  |  |
| habilitado | INTEGER | Sí |  |  |
| pendiente_revisar | INTEGER | Sí |  |  |
| id_entrevistador | INTEGER | Sí |  |  |
| id_reclasificado | INTEGER | Sí | FK → catalogos.cat_item |  |

---

## 3. `catalogos.criterio_fijo` — Opciones fijas del sistema

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_opcion | INTEGER | No | PK | Identificador |
| id_grupo | INTEGER | No |  | Grupo de opciones (1 = niveles de usuario) |
| descripcion | CHARACTER VARYING(255) | No |  | Texto de la opción |
| abreviado | CHARACTER VARYING(50) | Sí |  | Abreviatura |
| orden | INTEGER | Sí |  | Orden de visualización |
| habilitado | INTEGER | Sí |  | `1` = activo |

---

## 4. `catalogos.geo` — Geografía jerárquica

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_geo | INTEGER | No | PK | Identificador |
| id_padre | INTEGER | Sí | FK → catalogos.geo | Unidad geográfica padre |
| nivel | INTEGER | No |  | Nivel: 1=País, 2=Departamento, 3=Municipio |
| descripcion | CHARACTER VARYING(255) | No |  | Nombre de la unidad geográfica |
| id_tipo | INTEGER | Sí |  |  |
| codigo | CHARACTER VARYING(20) | Sí |  |  |
| lat | NUMERIC(10,7) | Sí |  |  |
| lon | NUMERIC(10,7) | Sí |  |  |
| codigo_2 | CHARACTER VARYING(20) | Sí |  |  |

---

## 5. `esclarecimiento.adjunto` — Archivos adjuntos

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_adjunto | INTEGER | No | PK | Identificador único |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente al que pertenece |
| ubicacion | CHARACTER VARYING(500) | Sí |  | Ruta del archivo en el sistema |
| nombre_original | CHARACTER VARYING(255) | Sí |  | Nombre original del archivo |
| tipo_mime | CHARACTER VARYING(100) | Sí |  | Tipo MIME del archivo |
| id_tipo | INTEGER | Sí | FK → catalogos.cat_item |  |
| id_calificacion | INTEGER | Sí |  |  |
| tamano | BIGINT | Sí |  |  |
| tamano_bruto | BIGINT | Sí |  |  |
| md5 | CHARACTER VARYING(32) | Sí |  |  |
| liviano_ubicacion | CHARACTER VARYING(500) | Sí |  |  |
| liviano_tamano | BIGINT | Sí |  |  |
| liviano_md5 | CHARACTER VARYING(32) | Sí |  |  |
| existe_archivo | INTEGER | Sí |  |  |
| duracion | INTEGER | Sí |  |  |
| texto_extraido | TEXT | Sí |  |  |
| texto_extraido_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| insert_fh | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| insert_ip | CHARACTER VARYING(45) | Sí |  |  |
| insert_ent | INTEGER | Sí |  |  |
| update_fh | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| update_ip | CHARACTER VARYING(45) | Sí |  |  |
| update_ent | INTEGER | Sí |  |  |

---

## 6. `esclarecimiento.asignacion_anonimizacion` — Asignaciones de anonimización

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_asignacion | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_anonimizador | INTEGER | Sí | FK → esclarecimiento.entrevistador | Anonimizador asignado |
| id_asignado_por | INTEGER | Sí | FK → public.users | Usuario que realizó la asignación |
| estado | CHARACTER VARYING(50) | Sí |  | Estado actual |
| fecha_asignacion | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de asignación |
| fecha_inicio_edicion | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de inicio de edición |
| fecha_envio_revision | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de envío a revisión |
| fecha_revision | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de revisión final |
| id_revisor | INTEGER | Sí | FK → public.users | Revisor designado |
| comentario_revision | TEXT | Sí |  | Comentario del revisor |
| tipos_anonimizar | CHARACTER VARYING(100) | Sí |  | Tipos de entidades a anonimizar |
| formato_reemplazo | CHARACTER VARYING(50) | Sí |  | Formato de sustitución de texto |
| texto_anonimizado | TEXT | Sí |  | Texto con reemplazos aplicados |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| segundos_edicion_activa | INTEGER | No |  | Tiempo activo de edición en segundos |

---

## 7. `esclarecimiento.asignacion_transcripcion` — Asignaciones de transcripción

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_asignacion | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_transcriptor | INTEGER | Sí | FK → esclarecimiento.entrevistador | Transcriptor asignado |
| id_asignado_por | INTEGER | Sí | FK → public.users | Usuario que realizó la asignación |
| estado | CHARACTER VARYING(50) | Sí |  | Estado actual (ver valores abajo) |
| fecha_asignacion | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de asignación |
| fecha_inicio_edicion | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de inicio de edición |
| fecha_envio_revision | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de envío a revisión |
| fecha_revision | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de revisión final |
| id_revisor | INTEGER | Sí | FK → public.users | Usuario revisor designado |
| comentario_revision | TEXT | Sí |  | Comentario del revisor |
| transcripcion_editada | TEXT | Sí |  | Texto de transcripción editado |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| id_adjunto | INTEGER | Sí | FK → esclarecimiento.adjunto | Archivo de audio/video a transcribir |
| calificacion_audio | SMALLINT | Sí |  | Calidad del audio (1–5) |
| observaciones_envio | TEXT | Sí |  | Observaciones al enviar a revisión |
| transcripcion_anotada | TEXT | Sí |  | Texto con anotaciones del revisor |
| segundos_edicion_activa | INTEGER | No |  | Tiempo activo de edición en segundos |
| historial_comentarios | JSONB | No |  | Array histórico de comentarios |

**Restricciones:**

- CHECK `asignacion_transcripcion_calificacion_audio_check`: `CHECK (((calificacion_audio >= 1) AND (calificacion_audio <= 5)))`

---

## 8. `esclarecimiento.compromiso_firma` — Firmas de compromisos

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_entrevistador | INTEGER | No | FK → esclarecimiento.entrevistador | Entrevistador firmante |
| tipo | CHARACTER VARYING(20) | No |  | Tipo: `acceso` o `reserva` |
| version_texto | CHARACTER VARYING(50) | No |  | Versión del texto firmado |
| fecha_firma | TIMESTAMP WITHOUT TIME ZONE | No |  | Fecha y hora de la firma |
| texto_firmado | TEXT | No |  | Texto completo del compromiso firmado |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |

**Restricciones:**

- CHECK `compromiso_firma_tipo_check`: `CHECK (((tipo)::text = ANY ((ARRAY['acceso'::character varying, 'reserva'::character varying])::text[])))`

---

## 9. `esclarecimiento.contenido_discapacidad` — Pivot: condiciones de discapacidad de las víctimas del expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_discapacidad | INTEGER | Sí | FK → catalogos.cat_item | Condición de discapacidad (cat_item) |

---

## 10. `esclarecimiento.contenido_etnia` — Pivot: grupos étnicos de las víctimas del expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_etnia | INTEGER | Sí | FK → catalogos.cat_item | Grupo étnico (cat_item) |

---

## 11. `esclarecimiento.contenido_hecho_victimizante` — Pivot: hechos victimizantes narrados en el expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_hecho | INTEGER | Sí | FK → catalogos.cat_item | Hecho victimizante (cat_item) |

---

## 12. `esclarecimiento.contenido_identidad_genero` — Pivot: identidades de género de las víctimas del expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_identidad | INTEGER | Sí | FK → catalogos.cat_item | Identidad de género (cat_item) |

---

## 13. `esclarecimiento.contenido_lugar` — Pivot: lugares (departamento/municipio) asociados a los hechos del expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_departamento | INTEGER | Sí | FK → catalogos.geo | Departamento de los hechos (geo) |
| id_municipio | INTEGER | Sí | FK → catalogos.geo | Municipio de los hechos (geo) |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de creación |

---

## 14. `esclarecimiento.contenido_ocupacion` — Pivot: ocupaciones de las víctimas del expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_ocupacion | INTEGER | Sí | FK → catalogos.cat_item | Ocupación (cat_item) |

---

## 15. `esclarecimiento.contenido_orientacion_sexual` — Pivot: orientaciones sexuales de las víctimas del expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_orientacion | INTEGER | Sí | FK → catalogos.cat_item | Orientación sexual (cat_item) |

---

## 16. `esclarecimiento.contenido_poblacion` — Pivot: poblaciones de especial protección del expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_poblacion | INTEGER | Sí | FK → catalogos.cat_item | Población de especial protección (cat_item) |

---

## 17. `esclarecimiento.contenido_practica_resistencia` — Pivot: prácticas de resistencia narradas en el expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_e_ind_fvt | INTEGER | No |  | Expediente asociado |
| id_practica | INTEGER | No |  | Práctica de resistencia (cat_item) |

---

## 18. `esclarecimiento.contenido_rango_etario` — Pivot: rangos etarios de las víctimas del expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_rango | INTEGER | Sí | FK → catalogos.cat_item | Rango etario (cat_item) |

---

## 19. `esclarecimiento.contenido_responsable` — Pivot: actores responsables señalados en el expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_responsable | INTEGER | Sí | FK → catalogos.cat_item | Actor responsable (cat_item) |

---

## 20. `esclarecimiento.contenido_sexo` — Pivot: sexo de las víctimas del expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_sexo | INTEGER | Sí | FK → catalogos.cat_item | Sexo (cat_item) |

---

## 21. `esclarecimiento.contenido_testimonio` — Contenido analítico

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_contenido | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente (relación 1:1) |
| fecha_hechos_inicial | DATE | Sí |  | Fecha inicial de los hechos |
| fecha_hechos_final | DATE | Sí |  | Fecha final de los hechos |
| responsables_individuales | TEXT | Sí |  | Texto de responsables individuales |
| temas_abordados | TEXT | Sí |  | Temas abordados en texto libre |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| otras_poblaciones_mencionadas | TEXT | Sí |  | Otras poblaciones en texto libre |
| otras_ocupaciones_mencionadas | TEXT | Sí |  | Otras ocupaciones en texto libre |
| detalle_grupos_etnicos | TEXT | Sí |  | Detalle de grupos étnicos mencionados |
| otros_hechos_victimizantes | TEXT | Sí |  | Otros hechos victimizantes |
| detalle_resistencias | TEXT | Sí |  | Detalle de prácticas de resistencia |
| fecha_hechos_inicial_dia_conocido | BOOLEAN | Sí |  | Día inicial exacto conocido |
| fecha_hechos_inicial_mes_conocido | BOOLEAN | Sí |  | Mes inicial exacto conocido |
| fecha_hechos_final_dia_conocido | BOOLEAN | Sí |  | Día final exacto conocido |
| fecha_hechos_final_mes_conocido | BOOLEAN | Sí |  | Mes final exacto conocido |

**Restricciones:**

- UNIQUE `contenido_testimonio_id_e_ind_fvt_key`: (`id_e_ind_fvt`)

---

## 22. `esclarecimiento.e_ind_fvt` — Entrevistas / Expedientes

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_e_ind_fvt | INTEGER | No | PK | Identificador único del expediente |
| id_subserie | INTEGER | Sí |  | Subserie documental |
| id_entrevistador | INTEGER | Sí | FK → esclarecimiento.entrevistador | Entrevistador responsable |
| id_macroterritorio | INTEGER | Sí | FK → catalogos.geo | Macroterritorio |
| id_territorio | INTEGER | Sí | FK → catalogos.geo | Territorio |
| entrevista_codigo | CHARACTER VARYING(50) | Sí |  | Código alfanumérico del expediente |
| entrevista_numero | INTEGER | Sí |  | Número correlativo de entrevista |
| entrevista_correlativo | INTEGER | Sí |  | Correlativo interno |
| entrevista_fecha | DATE | Sí |  | Fecha de la entrevista |
| numero_entrevistador | INTEGER | Sí |  | Número operativo del entrevistador |
| hechos_del | DATE | Sí |  | Inicio del período de hechos |
| hechos_al | DATE | Sí |  | Fin del período de hechos |
| hechos_lugar | INTEGER | Sí | FK → catalogos.geo | Lugar de los hechos narrados |
| entrevista_lugar | INTEGER | Sí | FK → catalogos.geo | Lugar donde se realizó la entrevista |
| anotaciones | TEXT | Sí |  | Anotaciones generales |
| titulo | CHARACTER VARYING(500) | Sí |  | Título descriptivo del testimonio |
| id_dependencia_origen | INTEGER | Sí | FK → catalogos.cat_item | Dependencia que genera el expediente |
| id_equipo_estrategia | INTEGER | Sí | FK → catalogos.cat_item | Equipo o estrategia asignada |
| nombre_proyecto | CHARACTER VARYING(500) | Sí |  | Nombre del proyecto asociado |
| id_tipo_testimonio | INTEGER | Sí | FK → catalogos.cat_item | Tipo de testimonio |
| num_testimoniantes | INTEGER | Sí |  | Número de testimoniantes (defecto: 1) |
| fecha_toma_inicial | DATE | Sí |  | Fecha de inicio de toma |
| fecha_toma_final | DATE | Sí |  | Fecha de fin de toma |
| id_idioma | INTEGER | Sí | FK → catalogos.cat_item | Idioma principal |
| tiene_anexos | INTEGER | Sí |  | Indica si hay documentos anexos |
| descripcion_anexos | TEXT | Sí |  | Descripción de los anexos |
| observaciones_toma | TEXT | Sí |  | Observaciones de la toma |
| seguimiento_revisado | CHARACTER VARYING(50) | Sí |  |  |
| seguimiento_finalizado | INTEGER | Sí |  |  |
| metadatos_ce | JSONB | Sí |  | Metadatos de Contribución al Esclarecimiento |
| metadatos_ca | JSONB | Sí |  | Metadatos de Caracterización |
| metadatos_da | JSONB | Sí |  | Metadatos de Daño |
| metadatos_ac | JSONB | Sí |  | Metadatos de Acciones Colectivas |
| nna | INTEGER | Sí |  | Involucra niños, niñas o adolescentes |
| tiempo_entrevista | INTEGER | Sí |  | Duración en segundos |
| clasifica_nna | INTEGER | Sí |  | Clasificación NNA (ponderado) |
| clasifica_sex | INTEGER | Sí |  | Clasificación género/sexo |
| clasifica_res | INTEGER | Sí |  | Clasificación de resistencias |
| clasifica_nivel | INTEGER | Sí |  | Clasificación de nivel |
| clasifica_r1 | INTEGER | Sí |  | Clasificación regional 1 |
| clasifica_r2 | INTEGER | Sí |  | Clasificación regional 2 |
| html_transcripcion | TEXT | Sí |  | HTML formateado de la transcripción |
| json_etiquetado | JSONB | Sí |  | Estructura de etiquetado temático |
| fts | TEXT | Sí |  | Texto para búsqueda de texto completo |
| id_cerrado | INTEGER | Sí |  | Expediente cerrado |
| fichas_alarmas | JSONB | Sí |  | Alarmas del sistema de fichas |
| fichas_estado | INTEGER | Sí |  |  |
| es_virtual | INTEGER | Sí |  | `1` = entrevista realizada virtualmente (campo INTEGER usado como bandera) |
| id_transcrita | INTEGER | Sí |  | Transcripción completada |
| id_etiquetada | INTEGER | Sí |  | Etiquetado completado |
| id_activo | INTEGER | Sí |  | `1` = expediente activo (campo INTEGER usado como bandera, no es FK pese al prefijo id_) |
| id_remitido | INTEGER | Sí |  | Remitido al archivo central |
| id_prioritario | INTEGER | Sí |  | Marcado como prioritario |
| prioritario_tema | TEXT | Sí |  | Tema de prioridad |
| id_sector | INTEGER | Sí | FK → catalogos.cat_item | Sector temático |
| id_etnico | INTEGER | Sí | FK → catalogos.cat_item | Pertenencia étnica del testimoniante |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría de timestamps |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría de timestamps |
| insert_fh | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| insert_ip | CHARACTER VARYING(45) | Sí |  |  |
| insert_ent | INTEGER | Sí |  |  |
| update_fh | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| update_ip | CHARACTER VARYING(45) | Sí |  |  |
| update_ent | INTEGER | Sí |  |  |
| transcripcion_completada_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de completado de transcripción |
| transcripcion_aprobada_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de aprobación de transcripción |
| transcripcion_aprobada_por | INTEGER | Sí |  | Usuario que aprobó la transcripción |
| transcripcion_final_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de transcripción final |
| transcripcion_final_por | INTEGER | Sí |  | Usuario que marcó transcripción final |
| entidades_detectadas_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de detección de entidades NER |
| anonimizacion_completada_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de completado de anonimización |
| anonimizacion_final | TEXT | Sí |  | Texto final anonimizado |
| anonimizacion_final_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| anonimizacion_final_por | INTEGER | Sí |  | Usuario que aprobó anonimización |
| detalle_idiomas | TEXT | Sí |  | Detalle de idiomas adicionales |
| nombre_entrevistador | TEXT | Sí |  | Nombre en texto libre del entrevistador |
| fecha_toma_inicial_dia_conocido | BOOLEAN | Sí |  | Indica si el día inicial es exacto |
| fecha_toma_inicial_mes_conocido | BOOLEAN | Sí |  | Indica si el mes inicial es exacto |
| fecha_toma_final_dia_conocido | BOOLEAN | Sí |  | Indica si el día final es exacto |
| fecha_toma_final_mes_conocido | BOOLEAN | Sí |  | Indica si el mes final es exacto |

---

## 23. `esclarecimiento.entidad_detectada` — Entidades NER

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_entidad | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente al que pertenece |
| tipo | CHARACTER VARYING(50) | Sí |  | Tipo de entidad reconocida |
| texto | TEXT | Sí |  | Texto original detectado |
| texto_anonimizado | CHARACTER VARYING(100) | Sí |  | Texto de reemplazo para anonimización |
| posicion_inicio | INTEGER | Sí |  | Posición de inicio en el texto |
| posicion_fin | INTEGER | Sí |  | Posición de fin en el texto |
| confianza | NUMERIC(5,4) | Sí |  | Nivel de confianza del modelo (0–1) |
| verificado | BOOLEAN | Sí |  | `true` = revisada por humano |
| excluir_anonimizacion | BOOLEAN | Sí |  | `true` = no se anonimiza |
| manual | BOOLEAN | Sí |  | `true` = agregada manualmente |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |

---

## 24. `esclarecimiento.entrevista_area_compatible` — Pivot: áreas de la CEV compatibles con el expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_area | INTEGER | Sí | FK → catalogos.cat_item | Área compatible (cat_item) |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de creación |

**Restricciones:**

- UNIQUE `entrevista_area_compatible_id_e_ind_fvt_id_area_key`: (`id_e_ind_fvt, id_area`)

---

## 25. `esclarecimiento.entrevista_formato` — Pivot: formatos de entrega del expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_formato | INTEGER | Sí | FK → catalogos.cat_item | Formato de entrega (cat_item) |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de creación |

---

## 26. `esclarecimiento.entrevista_idioma` — Pivot: idiomas presentes en la entrevista

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_e_ind_fvt | INTEGER | No |  | Expediente asociado |
| id_idioma | INTEGER | No |  | Idioma (cat_item) |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de creación |

---

## 27. `esclarecimiento.entrevista_modalidad` — Pivot: modalidades de toma de la entrevista

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_modalidad | INTEGER | Sí | FK → catalogos.cat_item | Modalidad de toma (cat_item) |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de creación |

---

## 28. `esclarecimiento.entrevista_necesidad_reparacion` — Pivot: necesidades de reparación identificadas en el expediente

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_necesidad | INTEGER | Sí | FK → catalogos.cat_item | Necesidad de reparación (cat_item) |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de creación |

---

## 29. `esclarecimiento.entrevistador` — Perfil operativo del usuario

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_entrevistador | INTEGER | No | PK | Identificador del perfil |
| id_usuario | INTEGER | Sí | FK → public.users | Usuario asociado |
| id_macroterritorio | INTEGER | Sí | FK → catalogos.geo | Macroterritorio de adscripción |
| id_territorio | INTEGER | Sí | FK → catalogos.geo | Territorio de adscripción |
| id_dependencia_origen | INTEGER | Sí | FK → catalogos.cat_item | Dependencia de origen |
| numero_entrevistador | INTEGER | Sí |  | Número de identificador operativo |
| id_ubicacion | INTEGER | Sí | FK → catalogos.geo | Ubicación actual |
| id_grupo | INTEGER | Sí | FK → catalogos.criterio_fijo | Grupo de trabajo |
| id_nivel | INTEGER | Sí | FK → catalogos.criterio_fijo |  |
| solo_lectura | INTEGER | Sí |  |  |
| compromiso_reserva | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| compromiso_acceso | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |

---

## 30. `esclarecimiento.failed_jobs` — Trabajos fallidos de la cola de Laravel

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | BIGINT | No | PK | Identificador |
| uuid | CHARACTER VARYING(255) | No |  | UUID único del trabajo |
| connection | TEXT | No |  | Conexión de cola usada |
| queue | TEXT | No |  | Nombre de la cola |
| payload | TEXT | No |  | Carga serializada del trabajo |
| exception | TEXT | No |  | Traza de la excepción |
| failed_at | TIMESTAMP WITHOUT TIME ZONE | No |  | Fecha del fallo |

**Restricciones:**

- UNIQUE `failed_jobs_uuid_key`: (`uuid`)

---

## 31. `esclarecimiento.importacion_expedientes` — Expedientes de importación

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_imp_expediente | INTEGER | No | PK | Identificador |
| id_importacion | INTEGER | No | FK → esclarecimiento.importaciones_masivas | Lote al que pertenece |
| id_csv | CHARACTER VARYING(100) | Sí |  | Identificador del expediente en el CSV |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente creado (nulo si aún no procesado) |
| estado | CHARACTER VARYING(50) | Sí |  | Estado: `pendiente`, `procesando`, `completado`, `error` |
| datos_csv | JSONB | Sí |  | Datos mapeados del CSV |
| filas_originales | JSONB | Sí |  | Filas originales del CSV |
| archivos | JSONB | Sí |  | Archivos vinculados al expediente |
| advertencias | JSONB | Sí |  | Lista de advertencias no bloqueantes |
| error_mensaje | TEXT | Sí |  | Mensaje de error si falló |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |

---

## 32. `esclarecimiento.importaciones_masivas` — Lotes de importación

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_importacion | INTEGER | No | PK | Identificador del lote |
| id_usuario | INTEGER | No | FK → public.users | Usuario que inició la importación |
| nombre_archivo | CHARACTER VARYING(500) | Sí |  | Nombre del archivo CSV original |
| ruta_csv | CHARACTER VARYING(1000) | Sí |  | Ruta en disco del CSV |
| estado | CHARACTER VARYING(50) | Sí |  | Estado del lote (ver valores) |
| total_expedientes | INTEGER | Sí |  | Total de expedientes en el lote |
| procesados | INTEGER | Sí |  | Expedientes procesados exitosamente |
| con_error | INTEGER | Sí |  | Expedientes con error |
| configuracion | JSONB | Sí |  | Configuración del mapeo de columnas |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |

---

## 33. `esclarecimiento.jobs` — Cola de trabajos asíncronos de Laravel

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | BIGINT | No | PK | Identificador |
| queue | CHARACTER VARYING(255) | No |  | Nombre de la cola |
| payload | TEXT | No |  | Carga serializada del trabajo |
| attempts | SMALLINT | No |  | Número de intentos |
| reserved_at | INTEGER | Sí |  | Marca de reserva por un worker |
| available_at | INTEGER | No |  | Marca a partir de la cual el trabajo está disponible |
| created_at | INTEGER | No |  | Fecha de encolado |

---

## 34. `esclarecimiento.permiso` — Permisos de acceso

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_permiso | INTEGER | No | PK | Identificador |
| id_entrevistador | INTEGER | Sí | FK → esclarecimiento.entrevistador | Entrevistador beneficiario |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente al que aplica |
| id_tipo | INTEGER | Sí |  |  |
| fecha_otorgado | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| fecha_vencimiento | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| justificacion | TEXT | Sí |  |  |
| id_otorgado_por | INTEGER | Sí | FK → esclarecimiento.entrevistador |  |
| id_estado | INTEGER | Sí |  |  |
| fecha_desde | DATE | Sí |  |  |
| fecha_hasta | DATE | Sí |  |  |
| id_adjunto | INTEGER | Sí |  | Adjunto específico (opcional) |
| id_revocado_por | INTEGER | Sí | FK → esclarecimiento.entrevistador |  |
| fecha_revocado | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| codigo_entrevista | CHARACTER VARYING(100) | Sí |  | Código de entrevista (referencia de texto) |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| es_solicitud | BOOLEAN | No |  |  |
| tipo_solicitud | CHARACTER VARYING(20) | Sí |  |  |
| estado_solicitud | CHARACTER VARYING(20) | Sí |  |  |
| fecha_solicitud | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| fecha_respuesta | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| id_respondido_por | INTEGER | Sí | FK → public.users |  |
| motivo_rechazo | TEXT | Sí |  |  |

**Restricciones:**

- CHECK `permiso_id_estado_check`: `CHECK ((id_estado = ANY (ARRAY[1, 2])))`
- CHECK `permiso_id_tipo_check`: `CHECK ((id_tipo = ANY (ARRAY[1, 2, 3])))`

---

## 35. `esclarecimiento.rol` — Roles de control de acceso

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_nivel | INTEGER | No | PK |  |
| nombre | CHARACTER VARYING(100) | No |  |  |
| descripcion | TEXT | Sí |  |  |
| es_sistema | BOOLEAN | Sí |  |  |
| habilitado | BOOLEAN | Sí |  |  |
| orden | INTEGER | Sí |  |  |

---

## 36. `esclarecimiento.rol_modulo_permiso` — Permisos por rol y módulo

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_permiso_rol | INTEGER | No | PK | Identificador |
| id_nivel | INTEGER | Sí | FK → esclarecimiento.rol |  |
| modulo | CHARACTER VARYING(50) | No |  |  |
| puede_ver | BOOLEAN | Sí |  |  |
| puede_crear | BOOLEAN | Sí |  |  |
| puede_editar | BOOLEAN | Sí |  |  |
| puede_eliminar | BOOLEAN | Sí |  |  |
| alcance_propias | BOOLEAN | Sí |  |  |
| alcance_dependencia | BOOLEAN | Sí |  |  |
| alcance_todas | BOOLEAN | Sí |  |  |

**Restricciones:**

- UNIQUE `rol_modulo_permiso_id_nivel_modulo_key`: (`id_nivel, modulo`)

---

## 37. `esclarecimiento.trabajo_procesamiento` — Trabajos automáticos

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_trabajo | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente procesado |
| tipo | CHARACTER VARYING(50) | Sí |  | Tipo de trabajo (transcripción, NER, etc.) |
| estado | CHARACTER VARYING(50) | Sí |  | Estado del trabajo |
| progreso | INTEGER | Sí |  | Porcentaje de avance (0–100) |
| parametros | JSONB | Sí |  | Parámetros del trabajo |
| id_usuario | INTEGER | Sí | FK → public.users | Usuario que inició el trabajo |
| mensaje | TEXT | Sí |  | Mensaje de estado o error |
| resultado | JSONB | Sí |  | Resultado del procesamiento |
| iniciado_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de inicio del procesamiento |
| completado_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de finalización |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |

---

## 38. `fichas.consentimiento_informado` — Consentimiento por persona

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_consentimiento | INTEGER | No | PK | Identificador |
| id_persona_entrevistada | INTEGER | Sí | FK → fichas.persona_entrevistada | Persona entrevistada asociada |
| tiene_documento_autorizacion | BOOLEAN | Sí |  | Cuenta con documento de autorización |
| es_menor_edad | BOOLEAN | Sí |  | Es menor de edad |
| autoriza_ser_entrevistado | BOOLEAN | Sí |  | Autoriza ser entrevistado |
| permite_grabacion | BOOLEAN | Sí |  | Permite grabación |
| permite_procesamiento_misional | BOOLEAN | Sí |  | Permite procesamiento misional |
| permite_uso_conservacion_consulta | BOOLEAN | Sí |  | Permite uso, conservación y consulta |
| considera_riesgo_seguridad | BOOLEAN | Sí |  | Considera que hay riesgo de seguridad |
| autoriza_datos_personales_sin_anonimizar | BOOLEAN | Sí |  | Autoriza datos personales sin anonimizar |
| autoriza_datos_sensibles_sin_anonimizar | BOOLEAN | Sí |  | Autoriza datos sensibles sin anonimizar |
| observaciones | TEXT | Sí |  | Observaciones |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| prueba_dano_derechos_privados | SMALLINT | Sí |  | Indicador codificado (SMALLINT) de prueba de daño a derechos privados; no almacena el texto de la prueba |
| prueba_dano_intereses_publicos | SMALLINT | Sí |  | Indicador codificado (SMALLINT) de prueba de daño a intereses públicos; no almacena el texto de la prueba |
| prueba_dano_inteligencia | SMALLINT | Sí |  | Indicador codificado (SMALLINT) de prueba de daño a información de inteligencia; no almacena el texto de la prueba |
| prueba_dano_nna | SMALLINT | Sí |  | Indicador codificado (SMALLINT) de prueba de daño a NNA; no almacena el texto de la prueba |

---

## 39. `fichas.entrevista` — Consentimiento e información de la entrevista

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_entrevista | INTEGER | No | PK | Identificador |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| id_idioma | INTEGER | Sí | FK → catalogos.cat_item | Idioma de la entrevista |
| id_nativo | INTEGER | Sí | FK → catalogos.cat_item | Idioma nativo del testimoniante |
| nombre_interprete | CHARACTER VARYING(200) | Sí |  | Nombre del intérprete |
| documentacion_aporta | INTEGER | Sí |  | Aporta documentación |
| documentacion_especificar | CHARACTER VARYING(500) | Sí |  | Descripción de la documentación |
| identifica_testigos | INTEGER | Sí |  | Identifica testigos adicionales |
| ampliar_relato | INTEGER | Sí |  | Puede ampliar el relato |
| ampliar_relato_temas | CHARACTER VARYING(500) | Sí |  | Temas para ampliar |
| priorizar_entrevista | INTEGER | Sí |  | Entrevista priorizada |
| priorizar_entrevista_asuntos | CHARACTER VARYING(500) | Sí |  | Asuntos de priorización |
| contiene_patrones | INTEGER | Sí |  | Contiene patrones de macro-criminalidad |
| contiene_patrones_cuales | CHARACTER VARYING(500) | Sí |  | Descripción de los patrones |
| indicaciones_transcripcion | CHARACTER VARYING(500) | Sí |  | Indicaciones para el transcriptor |
| observaciones | TEXT | Sí |  | Observaciones generales |
| identificacion_consentimiento | INTEGER | Sí |  | Consentimiento de identificación |
| conceder_entrevista | INTEGER | Sí |  | Concede la entrevista |
| grabar_audio | INTEGER | Sí |  | Autoriza grabación de audio |
| grabar_video | INTEGER | Sí |  | Autoriza grabación de video |
| tomar_fotografia | INTEGER | Sí |  | Autoriza toma de fotografías |
| elaborar_informe | INTEGER | Sí |  | Autoriza elaboración de informes |
| tratamiento_datos_analizar | INTEGER | Sí |  | Autoriza análisis de datos |
| tratamiento_datos_analizar_sensible | INTEGER | Sí |  | Autoriza análisis de datos sensibles |
| tratamiento_datos_utilizar | INTEGER | Sí |  | Autoriza uso de datos |
| tratamiento_datos_utilizar_sensible | INTEGER | Sí |  | Autoriza uso de datos sensibles |
| tratamiento_datos_publicar | INTEGER | Sí |  | Autoriza publicación |
| divulgar_material | INTEGER | Sí |  | Autoriza divulgación de material |
| traslado_info | INTEGER | Sí |  | Autoriza traslado de información |
| compartir_info | INTEGER | Sí |  | Autoriza compartir información |
| nombre_autoridad_etnica | CHARACTER VARYING(200) | Sí |  |  |
| nombre_identitario | CHARACTER VARYING(200) | Sí |  |  |
| pueblo_representado | CHARACTER VARYING(200) | Sí |  |  |
| id_pueblo_representado | INTEGER | Sí | FK → catalogos.cat_item |  |
| asistencia | INTEGER | Sí |  | Requiere asistencia |
| restrictiva | INTEGER | Sí |  | Entrevista con acceso restringido |
| borrable | INTEGER | Sí |  | Puede ser borrada (campo INTEGER usado como bandera; defecto: 0) |
| consentimiento_nombres | CHARACTER VARYING(200) | Sí |  |  |
| consentimiento_apellidos | CHARACTER VARYING(200) | Sí |  |  |
| consentimiento_sexo | INTEGER | Sí |  |  |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| insert_fh | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| insert_ip | CHARACTER VARYING(45) | Sí |  |  |
| insert_ent | INTEGER | Sí |  |  |
| update_fh | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| update_ip | CHARACTER VARYING(45) | Sí |  |  |
| update_ent | INTEGER | Sí |  |  |

---

## 40. `fichas.persona` — Datos personales

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_persona | INTEGER | No | PK | Identificador |
| nombre | CHARACTER VARYING(200) | Sí |  | Primer nombre |
| apellido | CHARACTER VARYING(200) | Sí |  | Apellido(s) |
| nombre_identitario | CHARACTER VARYING(200) | Sí |  | Nombre identitario |
| alias | CHARACTER VARYING(100) | Sí |  | Alias o nombre conocido |
| fec_nac_a | INTEGER | Sí |  | Año de nacimiento (la fecha se almacena descompuesta en a/m/d para permitir valores parcialmente conocidos) |
| fec_nac_m | INTEGER | Sí |  | Mes de nacimiento (1–12; nulo si no se conoce) |
| fec_nac_d | INTEGER | Sí |  | Día de nacimiento (1–31; nulo si no se conoce) |
| id_lugar_nacimiento | INTEGER | Sí | FK → catalogos.geo | Municipio de nacimiento |
| id_lugar_nacimiento_depto | INTEGER | Sí | FK → catalogos.geo | Departamento de nacimiento |
| id_sexo | INTEGER | Sí | FK → catalogos.cat_item | Sexo biológico |
| id_orientacion | INTEGER | Sí | FK → catalogos.cat_item | Orientación sexual |
| id_identidad | INTEGER | Sí | FK → catalogos.cat_item | Identidad de género |
| id_etnia | INTEGER | Sí | FK → catalogos.cat_item | Grupo étnico |
| id_etnia_indigena | INTEGER | Sí | FK → catalogos.cat_item | Pueblo indígena específico |
| id_rango_etario | INTEGER | Sí | FK → catalogos.cat_item | Rango etario |
| id_discapacidad | INTEGER | Sí | FK → catalogos.cat_item | Condición de discapacidad |
| id_tipo_documento | INTEGER | Sí | FK → catalogos.cat_item | Tipo de documento de identidad |
| num_documento | CHARACTER VARYING(50) | Sí |  | Número de documento |
| id_nacionalidad | INTEGER | Sí | FK → catalogos.cat_item | Nacionalidad principal |
| id_otra_nacionalidad | INTEGER | Sí | FK → catalogos.cat_item | Otra nacionalidad |
| id_estado_civil | INTEGER | Sí | FK → catalogos.cat_item | Estado civil |
| id_lugar_residencia | INTEGER | Sí | FK → catalogos.geo | Municipio de residencia |
| id_lugar_residencia_muni | INTEGER | Sí | FK → catalogos.geo | Municipio de residencia (detalle) |
| id_lugar_residencia_depto | INTEGER | Sí | FK → catalogos.geo | Departamento de residencia |
| lugar_residencia_nombre_vereda | CHARACTER VARYING(200) | Sí |  | Nombre de vereda de residencia |
| id_zona | INTEGER | Sí | FK → catalogos.cat_item | Zona (urbana/rural) |
| telefono | CHARACTER VARYING(50) | Sí |  | Teléfono de contacto |
| correo_electronico | CHARACTER VARYING(100) | Sí |  | Correo electrónico |
| id_edu_formal | INTEGER | Sí | FK → catalogos.cat_item | Nivel de educación formal |
| profesion | CHARACTER VARYING(200) | Sí |  | Profesión |
| ocupacion_actual | CHARACTER VARYING(200) | Sí |  | Ocupación actual (texto libre) |
| id_ocupacion_actual | INTEGER | Sí | FK → catalogos.cat_item | Ocupación actual (catálogo) |
| cargo_publico | INTEGER | Sí |  | Tiene/tuvo cargo público |
| cargo_publico_cual | CHARACTER VARYING(200) | Sí |  | Descripción del cargo público |
| id_fuerza_publica_estado | INTEGER | Sí | FK → catalogos.cat_item | Estado en la fuerza pública |
| fuerza_publica_especificar | CHARACTER VARYING(200) | Sí |  | Detalle de fuerza pública |
| id_fuerza_publica | INTEGER | Sí | FK → catalogos.cat_item | Rama de la fuerza pública |
| id_actor_armado | INTEGER | Sí | FK → catalogos.cat_item | Actor armado vinculado |
| actor_armado_especificar | CHARACTER VARYING(200) | Sí |  | Detalle de actor armado |
| organizacion_colectivo | INTEGER | Sí |  | Pertenece a organización/colectivo |
| nombre_organizacion | CHARACTER VARYING(200) | Sí |  | Nombre de la organización |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| insert_fh | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| insert_ip | CHARACTER VARYING(45) | Sí |  |  |
| insert_ent | INTEGER | Sí |  |  |
| update_fh | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| update_ip | CHARACTER VARYING(45) | Sí |  |  |
| update_ent | INTEGER | Sí |  |  |

---

## 41. `fichas.persona_entrevistada` — Relación persona ↔ entrevista

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_persona_entrevistada | INTEGER | No | PK | Identificador |
| id_persona | INTEGER | Sí | FK → fichas.persona | Persona involucrada |
| id_e_ind_fvt | INTEGER | Sí | FK → esclarecimiento.e_ind_fvt | Expediente asociado |
| es_victima | INTEGER | Sí |  | Participa como víctima |
| es_testigo | INTEGER | Sí |  | Participa como testigo |
| es_familiar | INTEGER | Sí |  | Participa como familiar |
| edad | INTEGER | Sí |  | Edad al momento de la entrevista |
| sintesis_relato | TEXT | Sí |  | Síntesis del relato |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Auditoría |
| insert_fh | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| insert_ip | CHARACTER VARYING(45) | Sí |  |  |
| insert_ent | INTEGER | Sí |  |  |
| update_fh | TIMESTAMP WITHOUT TIME ZONE | Sí |  |  |
| update_ip | CHARACTER VARYING(45) | Sí |  |  |
| update_ent | INTEGER | Sí |  |  |

---

## 42. `fichas.persona_ocupacion` — Pivot: ocupaciones adicionales de una persona

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_persona | INTEGER | Sí | FK → fichas.persona | Persona asociada |
| id_ocupacion | INTEGER | Sí | FK → catalogos.cat_item | Ocupación (cat_item) |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de creación |

**Restricciones:**

- UNIQUE `persona_ocupacion_id_persona_id_ocupacion_key`: (`id_persona, id_ocupacion`)

---

## 43. `fichas.persona_poblacion` — Pivot: poblaciones de especial protección de una persona

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| id_persona | INTEGER | Sí | FK → fichas.persona | Persona asociada |
| id_poblacion | INTEGER | Sí | FK → catalogos.cat_item | Población de especial protección (cat_item) |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de creación |

**Restricciones:**

- UNIQUE `persona_poblacion_id_persona_id_poblacion_key`: (`id_persona, id_poblacion`)

---

## 44. `public.migrations` — Historial de migraciones aplicadas por Laravel

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador |
| migration | CHARACTER VARYING(191) | No |  | Nombre del archivo de migración |
| batch | INTEGER | No |  | Lote de ejecución |

---

## 45. `public.traza_actividad` — Auditoría del sistema

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id_traza_actividad | BIGINT | No | PK | Identificador |
| fecha_hora | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha y hora exacta del evento |
| id_usuario | INTEGER | Sí | FK → public.users | Usuario que realizó la acción |
| accion | CHARACTER VARYING(100) | Sí |  | Acción ejecutada |
| objeto | CHARACTER VARYING(100) | Sí |  | Entidad sobre la que se actuó |
| id_registro | INTEGER | Sí |  | ID del registro afectado |
| referencia | CHARACTER VARYING(500) | Sí |  | Referencia textual (código de entrevista) |
| codigo | CHARACTER VARYING(100) | Sí |  | Código adicional de referencia |
| ip | CHARACTER VARYING(45) | Sí |  | Dirección IP del cliente |
| id_personificador | INTEGER | Sí | FK → public.users | Usuario que impersona (si aplica) |

---

## 46. `public.users` — Usuarios del sistema

| Columna | Tipo | Nulo | PK/FK | Descripción |
|---------|------|------|-------|-------------|
| id | INTEGER | No | PK | Identificador único |
| name | CHARACTER VARYING(255) | No |  | Nombre completo |
| email | CHARACTER VARYING(255) | No |  | Correo electrónico (login) |
| email_verified_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de verificación de correo |
| password | CHARACTER VARYING(255) | No |  | Hash de contraseña |
| is_login_directory_active | BOOLEAN | No |  | Indica si usa autenticación por directorio (LDAP) |
| remember_token | CHARACTER VARYING(100) | Sí |  | Token de sesión persistente |
| created_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de creación |
| updated_at | TIMESTAMP WITHOUT TIME ZONE | Sí |  | Fecha de última modificación |

**Restricciones:**

- UNIQUE `users_email_key`: (`email`)

---

## Valores de catálogo

> Solo ítems con `habilitado = 1`. Los catálogos marcados como editables se
> administran desde la aplicación, por lo que su contenido puede diferir entre servidores.

### `cat_cat` 1: Sexo (no editable, 3 ítems)

Sexo biológico

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 1 | Hombre | H |
| 2 | Mujer | M |
| 3 | Intersexual | I |

### `cat_cat` 2: Tipo de Documento (no editable, 6 ítems)

Tipos de documento de identidad

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 10 | Cédula de Ciudadanía | CC |
| 11 | Tarjeta de Identidad | TI |
| 12 | Cédula de Extranjería | CE |
| 13 | Pasaporte | PA |
| 14 | Registro Civil | RC |
| 15 | Sin Documento | SD |

### `cat_cat` 3: Grupo Étnico (no editable, 6 ítems)

Grupos étnicos

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 20 | Comunidades negras | CN |
| 21 | Pueblos indígenas | PI |
| 22 | Palenqueras | PA |
| 23 | Raizales | RA |
| 24 | Pueblo Rrom | RR |
| 25 | Ningún grupo étnico | NG |

### `cat_cat` 4: Dependencia de Origen (no editable, 12 ítems)

Áreas que realizaron la toma del testimonio

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 30 | Dirección Museo de Memoria y Conflicto | DMMC |
| 31 | Dirección de Construcción de Memoria Histórica | DCMH |
| 32 | Dirección de Acuerdos de la Verdad | DAV |
| 33 | Dirección de Archivo de los Derechos Humanos | DADH |
| 34 | Estrategia de Comunicaciones | EC |
| 35 | Dirección General | DG |
| 36 | Estrategia de Pedagogía | EP |
| 37 | Estrategia de Enfoques Diferenciales | EED |
| 38 | Estrategia Psicosocial | EPS |
| 39 | Estrategia de Territorialización | ET |
| 40 | Testimonio allegado al CNMH | TA |
| 330 | Observatorio de Memoria y Conflicto | OMC |

### `cat_cat` 5: Tipo de Testimonio (no editable, 5 ítems)

Clasificación según enfoque del testimonio

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 50 | Entrevista Individual | EI |
| 51 | Entrevista grupal/colectiva | EG |
| 52 | Entrevista a Profundidad | EP |
| 53 | Entrevista Estructurada | EE |
| 54 | Entrevista de Ampliación | EA |

### `cat_cat` 6: Formato del Testimonio (no editable, 4 ítems)

Formato en que fueron producidos los documentos

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 60 | Audio | AUD |
| 61 | Audiovisual | AV |
| 62 | Escrito | ESC |
| 63 | Otra índole | OTR |

### `cat_cat` 7: Modalidad (no editable, 3 ítems)

Forma en que se llevó a cabo la entrevista

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 70 | Virtual | VIR |
| 71 | Presencial | PRE |
| 72 | Sin Información | SI |

### `cat_cat` 8: Idioma (editable, 4 ítems)

Idiomas del testimonio

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 80 | Español | ES |
| 81 | Inglés | EN |
| 82 | Lengua nativa | LN |
| 325 | Otro(s) |  |

### `cat_cat` 9: Población (editable, 12 ítems)

Grupos sociales o comunitarios

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 90 | Líderes y/o lideresas | LID |
| 91 | Personas refugiadas | REF |
| 92 | Personas inmigrantes | INM |
| 93 | Personas exiliadas | EXI |
| 94 | Habitantes de calle | HAB |
| 95 | Personas desmovilizadas | DES |
| 96 | Menores desvinculados | MEN |
| 97 | Personas privadas de la libertad | PPL |
| 98 | Sindicalistas | SIN |
| 99 | Víctimas del conflicto armado | VIC |
| 100 | Ex miembro de Fuerza Pública | EFP |
| 331 | Experto(a) |  |

### `cat_cat` 10: Hecho Victimizante (no editable, 12 ítems)

Tipos de hechos victimizantes

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 110 | Acciones Bélicas | AB |
| 111 | Asesinatos Selectivos | AS |
| 112 | Atentado Terrorista | AT |
| 113 | Daño a Bienes Civiles | DB |
| 114 | Desaparición Forzada | DF |
| 115 | Masacres | MA |
| 116 | Reclutamiento de Menores | RU |
| 117 | Secuestro | SE |
| 118 | Violencia Sexual | VS |
| 119 | Ataque a Poblado | AP |
| 120 | Minas | MI |
| 121 | Desplazamiento forzado | DF |

### `cat_cat` 11: Ocupación (editable, 41 ítems)

Ocupaciones u oficios

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 361 | Administrador de Finca |  |
| 362 | Ama de Casa |  |
| 363 | Abogados |  |
| 364 | Artesanos |  |
| 365 | Campesinos |  |
| 131 | Comerciante | COM |
| 366 | Delincuente |  |
| 367 | Docentes |  |
| 368 | Economía Informal |  |
| 133 | Empleado | EMP |
| 369 | Empresario - Industrial |  |
| 370 | Estudiantes |  |
| 371 | Fuerza Pública |  |
| 372 | Funcionarios |  |
| 373 | Gestores de archivos |  |
| 374 | Ganadero/Hacendado |  |
| 375 | Guerrilleros |  |
| 376 | Miembro de Grupo Post-desmovilización |  |
| 377 | Mineros |  |
| 378 | Músicos |  |
| 379 | Obrero |  |
| 380 | Paramilitares |  |
| 381 | Periodistas |  |
| 382 | Pescadores |  |
| 137 | Profesional | PRO |
| 383 | Religioso |  |
| 384 | Sacerdotes |  |
| 385 | Seguridad Privada |  |
| 386 | Terrateniente |  |
| 387 | Trabajador de Finca |  |
| 388 | Trabajadores sexuales |  |
| 389 | Trabajadores de la salud |  |
| 390 | Transportadores |  |
| 391 | Turistas |  |
| 392 | Pensionado |  |
| 393 | Erradicador |  |
| 394 | Raspachines |  |
| 395 | Bandolero |  |
| 138 | Desempleado | DES |
| 396 | Trabajo Sin Especificar |  |
| 397 | Artista |  |

### `cat_cat` 12: Identidad de Género (no editable, 6 ítems)

Identidades de género

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 140 | Masculino | MAS |
| 141 | Femenino | FEM |
| 142 | Transgénero | TRA |
| 143 | No binario | NBI |
| 144 | Otro | OTR |
| 145 | Sin información | SI |

### `cat_cat` 13: Orientación Sexual (no editable, 5 ítems)

Orientaciones sexuales

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 150 | Heterosexual | HET |
| 151 | Homosexual | HOM |
| 152 | Bisexual | BIS |
| 153 | Otra | OTR |
| 154 | Sin información | SI |

### `cat_cat` 14: Rango Etario (no editable, 5 ítems)

Rangos de edad

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 332 | Niñas y Niños (0 a 13 años) |  |
| 333 | Adolescentes (14 a 17 años) |  |
| 334 | Jóvenes (18 a 27 años) |  |
| 335 | Personas adultas (27- 59 años) |  |
| 336 | Personas mayores (60 años o mas) |  |

### `cat_cat` 15: Discapacidad (no editable, 8 ítems)

Tipos de discapacidad

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 337 | Discapacidad física |  |
| 338 | Discapacidad funcional |  |
| 339 | Discapacidad intelectual |  |
| 340 | Discapacidad múltiple |  |
| 341 | Discapacidad psicosocial |  |
| 342 | Discapacidad sensorial |  |
| 170 | Ninguna | NIN |
| 343 | No especificada |  |

### `cat_cat` 16: Necesidad de Ruta de Reparación (no editable, 4 ítems)

Necesidades de ruta de reparación

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 180 | Administrativa | ADM |
| 181 | Simbólica | SIM |
| 182 | No | NO |
| 183 | No Aplica | NA |

### `cat_cat` 17: Responsable Colectivo (no editable, 18 ítems)

Grupos armados y otros responsables

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 344 | Agente del Estado |  |
| 345 | Organismos de inteligencia |  |
| 346 | Armada Nacional de Colombia |  |
| 347 | Fuerza Aérea Nacional de Colombia |  |
| 348 | Ejército Nacional de Colombia |  |
| 349 | Policía Nacional de Colombia |  |
| 350 | Autodefensas |  |
| 351 | Grupos armados disidentes |  |
| 352 | Grupos armados posdesmovilización |  |
| 353 | Grupos guerrilleros |  |
| 354 | Grupos paramilitares |  |
| 355 | Convivir |  |
| 356 | Juntas de autodefensa |  |
| 357 | Intervención militar extranjera |  |
| 358 | Bandolerismo |  |
| 359 | Bacrim |  |
| 360 | Grupo Armado No Identificado |  |
| 195 | Desconocido | DES |

### `cat_cat` 18: Equipo/Estrategia (editable, 17 ítems)

Equipos o estrategias por dependencia

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 200 | Dimensión Física | DF |
| 201 | Dimensión Territorial | DT |
| 202 | Dimensión Virtual | DV |
| 210 | Investigación para el Esclarecimiento | IPE |
| 211 | Iniciativas de Memoria Histórica | IMH |
| 212 | Reparaciones | REP |
| 220 | Esclarecimiento del fenómeno paramilitar | EFP |
| 221 | Contribuciones Voluntarias | CV |
| 230 | Testimonios | TEST |
| 231 | Fondos documentales | FD |
| 240 | Estrategia de Comunicaciones | EC |
| 250 | Dirección General | DG |
| 260 | Estrategia de Pedagogía | EP |
| 270 | Estrategia de Enfoques Diferenciales, Pueblos Étnicos y Campesinado | EEDPEC |
| 280 | Estrategia Psicosocial | EPS |
| 290 | Estrategia de Territorialización | ET |
| 300 | Testimonio allegado al CNMH | TACNMH |

### `cat_cat` 19: Tipo de Archivo Adjunto (no editable, 6 ítems)

Tipos de archivo adjunto a la entrevista

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 310 | Audio/Video de la entrevista | AV |
| 311 | Consentimiento informado | CI |
| 312 | Transcripcion Automatizada | TA |
| 313 | Transcripcion final | TF |
| 314 | Version publica | VP |
| 315 | Otros Documentos | OD |

### `cat_cat` 20: practicas_resistencia (editable, 9 ítems)

Prácticas de resistencia

| id_item | Descripción | Abreviado |
|---------|-------------|-----------|
| 316 | Prácticas de resistencia colectivas |  |
| 317 | Prácticas de resistencia cultural |  |
| 318 | Prácticas de resistencia de grupos específicos de personas |  |
| 319 | Prácticas de resistencia económica |  |
| 320 | Prácticas de resistencia espiritual |  |
| 321 | Prácticas de resistencia individuales |  |
| 322 | Prácticas de resistencia jurídica |  |
| 323 | Prácticas de resistencia política |  |
| 324 | Prácticas de resistencia social |  |

### `criterio_fijo` — opciones fijas por grupo

| id_grupo | id_opcion | Descripción | Abreviado |
|----------|-----------|-------------|-----------|
| 1 | 1 | Administrador | Admin |
| 1 | 2 | Líder | Líder |
| 1 | 3 | Entrevistador | Ent |
| 1 | 4 | Transcriptor | Trans |
| 1 | 5 | Gestor de Conocimiento |  |
| 1 | 99 | Deshabilitado | Des |
| 21 | 21 | Crear | C |
| 21 | 22 | Leer | R |
| 21 | 23 | Actualizar | U |
| 21 | 24 | Eliminar | D |
| 21 | 25 | Login | L |
| 21 | 26 | Logout | O |
| 22 | 31 | Entrevista | Ent |
| 22 | 32 | Persona | Per |
| 22 | 33 | Adjunto | Adj |
| 22 | 34 | Usuario | Usr |
| 22 | 35 | Permiso | Prm |

---

