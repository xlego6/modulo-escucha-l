<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Aplica las descripciones del diccionario (COMMENT ON) en el despliegue.
 *
 * Delega en el comando `dic:comentar`, cuya fuente es el archivo versionado
 * database/diccionario/descripciones.php. Así, al correr `php artisan migrate`
 * en pruebas y en producción, ambas bases quedan con comentarios idénticos y
 * `php artisan dic:generar` produce el mismo Markdown en las dos máquinas.
 *
 * El comando es idempotente y tolera drift (omite tablas/columnas inexistentes),
 * por lo que es seguro re-ejecutar esta migración tras un rollback.
 */
class AplicarComentariosDiccionario extends Migration
{
    public function up()
    {
        Artisan::call('dic:comentar');
    }

    public function down()
    {
        // Los comentarios son metadatos no destructivos; no se revierten.
    }
}
