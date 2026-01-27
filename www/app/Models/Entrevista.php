<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Entrevista extends Model
{
    protected $table = 'esclarecimiento.e_ind_fvt';
    protected $primaryKey = 'id_e_ind_fvt';

    // Constantes para tipos de adjunto (catálogo 19)
    const TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA = 312;
    const TIPO_ADJUNTO_TRANSCRIPCION_FINAL = 313;

    protected $fillable = [
        'id_subserie',
        'id_entrevistador',
        'id_macroterritorio',
        'id_territorio',
        'entrevista_codigo',
        'entrevista_numero',
        'entrevista_correlativo',
        'entrevista_fecha',
        'numero_entrevistador',
        'hechos_del',
        'hechos_al',
        'hechos_lugar',
        'entrevista_lugar',
        'anotaciones',
        'titulo',
        'nna',
        'tiempo_entrevista',
        'es_virtual',
        'id_activo',
        'id_sector',
        'id_etnico',
        // Nuevos campos Paso 1
        'id_dependencia_origen',
        'id_equipo_estrategia',
        'nombre_proyecto',
        'id_tipo_testimonio',
        'num_testimoniantes',
        'id_idioma',
        'tiene_anexos',
        'descripcion_anexos',
        'fecha_toma_inicial',
        'fecha_toma_final',
        'id_area_compatible',
        'observaciones_toma',
    ];

    protected $casts = [
        'metadatos_ce' => 'array',
        'metadatos_ca' => 'array',
        'metadatos_da' => 'array',
        'metadatos_ac' => 'array',
        'fichas_alarmas' => 'array',
        'json_etiquetado' => 'array',
    ];

    public function rel_entrevistador() {
        return $this->belongsTo(Entrevistador::class, 'id_entrevistador', 'id_entrevistador');
    }

    public function rel_adjuntos() {
        return $this->hasMany(Adjunto::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    public function rel_consentimiento() {
        return $this->hasOne(Consentimiento::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    public function rel_personas_entrevistadas() {
        return $this->hasMany(PersonaEntrevistada::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    public function rel_lugar_entrevista() {
        return $this->belongsTo(Geo::class, 'entrevista_lugar', 'id_geo');
    }

    public function rel_lugar_hechos() {
        return $this->belongsTo(Geo::class, 'hechos_lugar', 'id_geo');
    }

    // Nuevas relaciones Paso 1
    public function rel_dependencia_origen() {
        return $this->belongsTo(CatItem::class, 'id_dependencia_origen', 'id_item');
    }

    public function rel_tipo_testimonio() {
        return $this->belongsTo(CatItem::class, 'id_tipo_testimonio', 'id_item');
    }

    public function rel_idioma() {
        return $this->belongsTo(CatItem::class, 'id_idioma', 'id_item');
    }

    public function rel_area_compatible() {
        return $this->belongsTo(CatItem::class, 'id_area_compatible', 'id_item');
    }

    public function rel_equipo_estrategia() {
        return $this->belongsTo(CatItem::class, 'id_equipo_estrategia', 'id_item');
    }

    public function rel_formatos() {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.entrevista_formato', 'id_e_ind_fvt', 'id_formato', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_modalidades() {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.entrevista_modalidad', 'id_e_ind_fvt', 'id_modalidad', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_necesidades_reparacion() {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.entrevista_necesidad_reparacion', 'id_e_ind_fvt', 'id_necesidad', 'id_e_ind_fvt', 'id_item');
    }

    // Relación Paso 3 - Contenido
    public function rel_contenido() {
        return $this->hasOne(ContenidoTestimonio::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    public function getFmtFechaAttribute() {
        if (empty($this->entrevista_fecha)) {
            return 'Sin fecha';
        }
        try {
            $fecha = Carbon::createFromFormat('Y-m-d', $this->entrevista_fecha);
            return $fecha->format('d/m/Y');
        } catch (\Exception $e) {
            return $this->entrevista_fecha;
        }
    }

    public function getFmtCodigoAttribute() {
        return $this->entrevista_codigo ?? 'Sin código';
    }

    public function getFmtTituloAttribute() {
        return $this->titulo ?? 'Sin título';
    }

    public static function filtros_default() {
        return [
            'id_activo' => 1,
        ];
    }

    // =============================================
    // MÉTODOS PARA TRANSCRIPCIÓN AUTOMATIZADA
    // =============================================

    /**
     * Obtener el adjunto de transcripción automatizada
     */
    public function getAdjuntoTranscripcion()
    {
        return $this->rel_adjuntos()
            ->where('id_tipo', self::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Obtener el texto de la transcripción automatizada
     */
    public function getTranscripcionAutomatizada()
    {
        $adjunto = $this->getAdjuntoTranscripcion();
        return $adjunto ? $adjunto->texto_extraido : null;
    }

    /**
     * Verificar si tiene transcripción automatizada
     */
    public function tieneTranscripcionAutomatizada()
    {
        return $this->rel_adjuntos()
            ->where('id_tipo', self::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA)
            ->whereNotNull('texto_extraido')
            ->where('texto_extraido', '!=', '')
            ->exists();
    }

    /**
     * Guardar transcripción automatizada como adjunto
     * @param string $texto Texto de la transcripción
     * @param string|null $nombreArchivo Nombre del archivo de origen (opcional)
     * @return Adjunto
     */
    public function guardarTranscripcionAutomatizada($texto, $nombreArchivo = null)
    {
        $adjunto = $this->getAdjuntoTranscripcion();

        if ($adjunto) {
            // Actualizar adjunto existente
            $adjunto->texto_extraido = $texto;
            $adjunto->texto_extraido_at = now();
            $adjunto->save();
        } else {
            // Crear nuevo adjunto
            $codigo = $this->entrevista_codigo ?? 'SIN-CODIGO';
            $nombre = $nombreArchivo ?? 'transcripcion_automatizada_' . $codigo . '.txt';

            $adjunto = Adjunto::create([
                'id_e_ind_fvt' => $this->id_e_ind_fvt,
                'id_tipo' => self::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA,
                'nombre_original' => $nombre,
                'tipo_mime' => 'text/plain',
                'ubicacion' => '', // No hay archivo físico, solo texto
                'tamano' => strlen($texto),
                'texto_extraido' => $texto,
                'texto_extraido_at' => now(),
                'existe_archivo' => 0,
            ]);
        }

        // Actualizar fecha de transcripción completada
        $this->transcripcion_completada_at = now();
        $this->save();

        return $adjunto;
    }

    /**
     * Obtener texto para procesamiento (transcripción automatizada o anotaciones legacy)
     * Mantiene compatibilidad con datos anteriores
     */
    public function getTextoParaProcesamiento()
    {
        // Primero intentar obtener del adjunto de transcripción
        $transcripcion = $this->getTranscripcionAutomatizada();
        if ($transcripcion) {
            return $transcripcion;
        }

        // Fallback a anotaciones (compatibilidad con datos existentes)
        return $this->anotaciones;
    }

    // =============================================
    // MÉTODOS PARA TRANSCRIPCIÓN FINAL
    // =============================================

    /**
     * Obtener el adjunto de transcripción final
     */
    public function getAdjuntoTranscripcionFinal()
    {
        return $this->rel_adjuntos()
            ->where('id_tipo', self::TIPO_ADJUNTO_TRANSCRIPCION_FINAL)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Obtener el texto de la transcripción final
     */
    public function getTranscripcionFinal()
    {
        $adjunto = $this->getAdjuntoTranscripcionFinal();
        return $adjunto ? $adjunto->texto_extraido : null;
    }

    /**
     * Verificar si tiene transcripción final
     */
    public function tieneTranscripcionFinal()
    {
        return $this->rel_adjuntos()
            ->where('id_tipo', self::TIPO_ADJUNTO_TRANSCRIPCION_FINAL)
            ->whereNotNull('texto_extraido')
            ->where('texto_extraido', '!=', '')
            ->exists();
    }

    /**
     * Guardar transcripción final como adjunto
     * @param string $texto Texto de la transcripción final
     * @param int|null $aprobadoPor ID del usuario que aprobó
     * @return Adjunto
     */
    public function guardarTranscripcionFinal($texto, $aprobadoPor = null)
    {
        $adjunto = $this->getAdjuntoTranscripcionFinal();

        if ($adjunto) {
            // Actualizar adjunto existente
            $adjunto->texto_extraido = $texto;
            $adjunto->texto_extraido_at = now();
            $adjunto->save();
        } else {
            // Crear nuevo adjunto
            $codigo = $this->entrevista_codigo ?? 'SIN-CODIGO';
            $nombre = 'transcripcion_final_' . $codigo . '.txt';

            $adjunto = Adjunto::create([
                'id_e_ind_fvt' => $this->id_e_ind_fvt,
                'id_tipo' => self::TIPO_ADJUNTO_TRANSCRIPCION_FINAL,
                'nombre_original' => $nombre,
                'tipo_mime' => 'text/plain',
                'ubicacion' => '',
                'tamano' => strlen($texto),
                'texto_extraido' => $texto,
                'texto_extraido_at' => now(),
                'existe_archivo' => 0,
            ]);
        }

        // Actualizar campos de transcripción final en la entrevista
        $this->transcripcion_final_at = now();
        if ($aprobadoPor) {
            $this->transcripcion_final_por = $aprobadoPor;
        }
        $this->save();

        return $adjunto;
    }
}
