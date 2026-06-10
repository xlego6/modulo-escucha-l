<?php

/**
 * Claves foráneas que el esquema debería tener pero no fueron declaradas.
 *
 * Fuente única de verdad para:
 *   - el verificador de huérfanos  (php artisan fk:verificar)
 *   - la migración que las agrega   (..._agregar_fks_faltantes_usuarios.php)
 *
 * Todas las columnas listadas son nullable y referencian public.users(id)
 * (relaciones "quién realizó la acción" / "creado por"). Se confirmaron contra
 * las relaciones belongsTo de los modelos Eloquent.
 *
 * NO se incluyen permiso.id_otorgado_por / id_revocado_por: referencian
 * esclarecimiento.entrevistador y ya tienen FK declarada.
 *
 * on_delete = SET NULL: borrar un usuario no debe bloquearse ni borrar el
 * registro de trabajo/asignación; solo se pierde el "quién". Cámbialo a
 * 'NO ACTION' si se prefiere bloquear el borrado de usuarios referenciados.
 */

return [
    'on_delete' => 'SET NULL',

    'fks' => [
        // Preexistente en pruebas, ausente en producción (drift detectado el 2026-06-10).
        // La constraint se llamaría entrevistador_id_usuario_fkey: el nombre ya existe en
        // pruebas, así que la migración la OMITE allí y la crea solo donde falta (prod).
        ['esquema' => 'esclarecimiento', 'tabla' => 'entrevistador',            'columna' => 'id_usuario'],

        ['esquema' => 'esclarecimiento', 'tabla' => 'importaciones_masivas',     'columna' => 'id_usuario'],
        ['esquema' => 'esclarecimiento', 'tabla' => 'trabajo_procesamiento',     'columna' => 'id_usuario'],
        ['esquema' => 'esclarecimiento', 'tabla' => 'asignacion_transcripcion',  'columna' => 'id_asignado_por'],
        ['esquema' => 'esclarecimiento', 'tabla' => 'asignacion_transcripcion',  'columna' => 'id_revisor'],
        ['esquema' => 'esclarecimiento', 'tabla' => 'asignacion_anonimizacion',  'columna' => 'id_asignado_por'],
        ['esquema' => 'esclarecimiento', 'tabla' => 'asignacion_anonimizacion',  'columna' => 'id_revisor'],
        ['esquema' => 'esclarecimiento', 'tabla' => 'permiso',                   'columna' => 'id_respondido_por'],
    ],

    // Tabla y columna destino (común a todas las FKs de arriba).
    'ref_esquema' => 'public',
    'ref_tabla'   => 'users',
    'ref_columna' => 'id',
];
