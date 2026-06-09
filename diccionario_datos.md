# Diccionario de Datos — Guía semántica complementaria

> **Documento canónico de estructura:** [`www/diccionario_datos_generado.md`](www/diccionario_datos_generado.md)
> Ese archivo se genera por introspección directa de la BD con `php artisan dic:generar`
> y siempre refleja tipos, nulabilidad, PK y FK **reales**. Es la fuente de verdad de la
> *estructura*.
>
> **Este archivo** complementa al anterior con lo que la introspección no puede aportar:
> enumeraciones de valores, máquinas de estado, códigos de catálogo y notas de diseño.
> No repite las tablas columna-por-columna para evitar contradecir a la BD.

**Motor:** PostgreSQL 11 · **Base de datos:** `testimonios`
**Esquemas:** `public`, `esclarecimiento`, `fichas`, `catalogos` · **ORM:** Laravel Eloquent

Para el diagrama entidad-relación y los flujos de datos, ver [`modelo_er.md`](modelo_er.md).

---

## Convenciones y advertencias de lectura

Antes de interpretar la estructura generada, ten presentes estas particularidades del
esquema actual (ver "Deuda técnica conocida" al final):

- **Banderas booleanas almacenadas como `INTEGER`.** Gran parte de los sí/no del núcleo
  legado (`e_ind_fvt`, `fichas.*`, `adjunto.existe_archivo`, `entrevistador.solo_lectura`)
  son `INTEGER` con semántica `1 = sí`, `0 = no`, `NULL = sin dato`. Solo las tablas nuevas
  (`entidad_detectada`, `compromiso_firma`, `rol`, `rol_modulo_permiso`, `users`) usan el
  tipo `BOOLEAN` real.
- **Prefijo `id_` que NO siempre es clave foránea.** En `e_ind_fvt`, columnas como
  `id_activo`, `id_cerrado`, `id_transcrita`, `id_etiquetada`, `id_remitido`,
  `id_prioritario` son banderas 0/1, no identificadores ni FK.
- **Doble auditoría.** Conviven los campos legados `insert_fh / insert_ip / insert_ent` y
  `update_fh / update_ip / update_ent` con los `created_at / updated_at` de Laravel. La
  auditoría funcional vive además en `traza_actividad`.

---

## Catálogo de valores y enumeraciones

### `esclarecimiento.rol` — roles del sistema (`id_nivel`)

| id_nivel | nombre |
|----------|--------|
| 1 | Administrador |
| 2 | Líder |
| 3 | Entrevistador |
| 4 | Transcriptor |
| 5 | Gestor de Conocimiento |
| ≥ 10 | Roles personalizados |

### `esclarecimiento.rol_modulo_permiso` — módulos (`modulo`)

`entrevistas`, `adjuntos`, `personas`, `buscador`, `estadísticas`, `mapa`, `exportar`,
`procesamientos`, `procesamientos.transcripcion`, `procesamientos.edicion`,
`procesamientos.entidades`, `procesamientos.anonimizacion`, `permisos`, `usuarios`,
`catálogos`, `traza`, `roles`.

Cada módulo se combina con `puede_ver / puede_crear / puede_editar / puede_eliminar` y un
alcance (`alcance_propias`, `alcance_dependencia`, `alcance_todas`).

### `esclarecimiento.adjunto` — tipos de adjunto relevantes (`id_tipo` → `cat_item`)

| id_tipo | Descripción |
|---------|-------------|
| 312 | Transcripción automatizada |
| 313 | Transcripción final |
| 314 | Transcripción anonimizada |

### `esclarecimiento.asignacion_transcripcion` y `asignacion_anonimizacion` — estados

```
asignada → en_edicion → enviada_revision → aprobada
                                         └→ rechazada
```

### `esclarecimiento.entidad_detectada` — tipos de entidad (`tipo`)

`PER` (Persona), `LOC` (Lugar), `ORG` (Organización), `DATE` (Fecha), `EVENT` (Evento),
`GUN` (Arma), `MISC` (Miscelánea).

### `esclarecimiento.permiso` — códigos (enteros sin FK, "valores mágicos")

| Columna | Valores |
|---------|---------|
| `id_tipo` | 1 = lectura · 2 = escritura · 3 = completo |
| `id_estado` | 1 = vigente · 2 = revocado |
| `tipo_solicitud` | `acceso` · `edicion` · `eliminacion` |
| `estado_solicitud` | `pendiente` · `aprobado` · `rechazado` · `revocado` |

`es_solicitud = true` distingue una solicitud pendiente de un permiso ya otorgado.

### `esclarecimiento.importaciones_masivas` — estados del lote

```
pendiente → mapeando → confirmado → procesando → completado
                                              └→ con_errores
```

`importacion_expedientes.estado`: `pendiente`, `procesando`, `completado`, `error`.

### `esclarecimiento.compromiso_firma` — tipo

`acceso` o `reserva`.

### `catalogos.geo` — niveles (`nivel`)

1 = País · 2 = Departamento · 3 = Municipio (jerarquía vía `id_padre`).

### `catalogos.criterio_fijo` — grupos (`id_grupo`)

`1` = niveles de usuario. (Catálogo de opciones fijas no editables por administradores.)

### `public.traza_actividad` — acciones registradas (`accion`)

`crear`, `editar`, `eliminar`, `ver`, `descargar`, `subir`, `login`, `logout`, `exportar`,
`buscar`, `aceptar_compromiso`, `crear_perfil_automatico`, `subir_adjunto`,
`descargar_adjunto`, `eliminar_adjunto`, `exportar_entrevistas`, `exportar_personas`,
`crear_persona`, `editar_persona`, `eliminar_persona`, `crear_usuario`, `actualizar_usuario`,
`eliminar_usuario`, `crear_rol`, `actualizar_permisos_rol`, `eliminar_rol`, `otorgar_permiso`,
`revocar_permiso`, `desclasificar`, `solicitar_permiso`, `aprobar_solicitud`,
`rechazar_solicitud`, `iniciar_transcripcion`, `editar_transcripcion`,
`aprobar_transcripcion`, `rechazar_transcripcion`, `enviar_revision`, `asignar_transcripcion`,
`editar_anonimizacion`, `aprobar_anonimizacion`, `rechazar_anonimizacion`,
`asignar_anonimizacion`.

---

## Relaciones muchos-a-muchos (tablas pivot)

Cada pivot relaciona el expediente (`id_e_ind_fvt`) o la persona (`id_persona`) con un ítem
de catálogo (`cat_item`) o, en `contenido_lugar`, con `geo`.

| Pivot | Relaciona expediente/persona con… |
|-------|-----------------------------------|
| `contenido_poblacion` | Poblaciones de especial protección |
| `contenido_ocupacion` | Ocupaciones de las víctimas |
| `contenido_sexo` | Sexo de las víctimas |
| `contenido_identidad_genero` | Identidades de género |
| `contenido_orientacion_sexual` | Orientaciones sexuales |
| `contenido_etnia` | Grupos étnicos |
| `contenido_rango_etario` | Rangos etarios |
| `contenido_discapacidad` | Condiciones de discapacidad |
| `contenido_hecho_victimizante` | Hechos victimizantes |
| `contenido_responsable` | Actores responsables |
| `contenido_practica_resistencia` | Prácticas de resistencia |
| `contenido_lugar` | Departamento / municipio de los hechos (`geo`) |
| `entrevista_formato` | Formatos de entrega |
| `entrevista_modalidad` | Modalidades de toma |
| `entrevista_necesidad_reparacion` | Necesidades de reparación |
| `entrevista_idioma` | Idiomas presentes |
| `entrevista_area_compatible` | Áreas de la CEV compatibles |
| `persona_poblacion` | Poblaciones de especial protección (de la persona) |
| `persona_ocupacion` | Ocupaciones adicionales (de la persona) |

> **Inconsistencia:** la mayoría de los pivots tienen PK surrogada `id`, FKs declaradas y
> `created_at`; `contenido_practica_resistencia` y `entrevista_idioma` **no** tienen PK ni
> FK ni `created_at`.

---

## Deuda técnica conocida del esquema

Resumen de los puntos a resolver gradualmente (no son errores del diccionario, sino del
modelo de datos actual):

1. Prefijo `id_` en banderas booleanas (`id_activo`, `id_cerrado`, `id_transcrita`, …).
2. Booleanos como `INTEGER` en el núcleo legado en vez de `BOOLEAN`.
3. Doble/triple auditoría (`insert_*`/`update_*` + `created_at`/`updated_at` + `traza_actividad`).
4. Integridad referencial débil: FKs nullable y varios `id_*` lógicos sin constraint
   (`importaciones_masivas.id_usuario`, `trabajo_procesamiento.id_usuario`,
   `asignacion_*.id_asignado_por/id_revisor`, `permiso.id_respondido_por`); `permiso.id_tipo`
   e `id_estado` con valores mágicos sin catálogo.
5. Triplicación de fechas de hechos (`e_ind_fvt.hechos_del/hechos_al` +
   `e_ind_fvt.fecha_toma_inicial/final` + `contenido_testimonio.fecha_hechos_inicial/final`).
6. Residencia de persona con tres columnas ambiguas (`id_lugar_residencia`,
   `id_lugar_residencia_muni`, `id_lugar_residencia_depto`).
7. Pivots con diseño inconsistente entre sí (ver nota arriba).
8. `e_ind_fvt` como tabla "monstruo" (~75 columnas) que mezcla identificación, clasificación,
   estados de procesamiento, FTS, auditoría legada y 4 JSONB opacos (`metadatos_ce/ca/da/ac`).
9. `consentimiento_informado.prueba_dano_*` son `SMALLINT` (indicadores codificados), no el
   texto de la prueba.
