<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntidadDetectada extends Model
{
    protected $table = 'esclarecimiento.entidad_detectada';
    protected $primaryKey = 'id_entidad';

    protected $fillable = [
        'id_e_ind_fvt',
        'tipo',
        'texto',
        'texto_anonimizado',
        'posicion_inicio',
        'posicion_fin',
        'confianza',
        'verificado',
        'excluir_anonimizacion',
        'manual',
        'grupo',
    ];

    protected $casts = [
        'verificado' => 'boolean',
        'excluir_anonimizacion' => 'boolean',
        'manual' => 'boolean',
        'confianza' => 'float',
    ];

    /**
     * Relación con la entrevista
     */
    public function entrevista()
    {
        return $this->belongsTo(Entrevista::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    /**
     * Scope para filtrar por tipo de entidad
     */
    public function scopeOfType($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Taxonomía de etiquetas de anonimización (basada en la guía de anonimización
     * del expediente testimonial: insumoanon.xlsx / insumoarbol.txt).
     */
    public static function tipos()
    {
        return [
            'NUMERO' => 'Número/identificador',
            'PERSONA' => 'Persona',
            'ORGANIZACION' => 'Organización',
            'LUGAR' => 'Lugar',
            'GENTILICIO' => 'Gentilicio',
            'FECHA' => 'Fecha',
            'EDAD' => 'Edad',
            'OCUPACION' => 'Ocupación',
            'GRUPO_ARMADO' => 'Grupo armado',
            'ROL_ARMADO' => 'Rol en grupo armado',
            'ETNICO' => 'Étnico',
        ];
    }

    /**
     * Mapeo de las etiquetas que produce el modelo spaCy estándar en español
     * (es_core_news_lg/sm: PER, LOC, ORG, MISC) a la taxonomía propia. MISC no
     * tiene equivalente claro y se descarta deliberadamente: solo PERSONA, LUGAR
     * y ORGANIZACION se detectan automáticamente; el resto de la taxonomía
     * (NUMERO, GENTILICIO, FECHA, EDAD, OCUPACION, GRUPO_ARMADO, ROL_ARMADO,
     * ETNICO) solo se puede etiquetar manualmente en el editor.
     */
    public static function mapeoDeteccionAutomatica()
    {
        return [
            'PER' => 'PERSONA',
            'LOC' => 'LUGAR',
            'ORG' => 'ORGANIZACION',
        ];
    }

    /**
     * Tipos que por defecto vienen marcados para cubrir/anonimizar (la guía los
     * trata como "anonimizar siempre"); el resto queda desmarcado por defecto
     * ("en general no se anonimiza, excepto..."). Es solo un valor inicial de
     * checkbox, ajustable por documento.
     */
    public static function tiposPorDefecto()
    {
        return ['PERSONA', 'LUGAR', 'NUMERO', 'ORGANIZACION', 'GRUPO_ARMADO', 'ETNICO'];
    }

    /**
     * Textos que el NER marca como PERSONA/LUGAR/... pero no son entidades a
     * anonimizar: pronombres y rotulos de hablante de la transcripcion
     * ("Edo.", "Eda.", "Entr."). Se comparan normalizados (ver normalizar()).
     */
    public static function textosExcluidos()
    {
        return [
            'yo', 'tu', 'vos', 'usted', 'ustedes', 'el', 'ella', 'ello', 'ellos', 'ellas',
            'nosotros', 'nosotras', 'vosotros', 'vosotras', 'mi', 'me', 'nos', 'les', 'le',
            'edo', 'eda', 'entr', 'ent', 'entrevistador', 'entrevistadora',
            'entrevistado', 'entrevistada', 'don', 'dona', 'senor', 'senora',
        ];
    }

    /**
     * Forma normalizada de un texto para comparar variantes de la misma entidad:
     * sin acentos, minusculas, sin marcas markdown ni puntuacion, espacios
     * colapsados ("Carlos Hernández." == "carlos hernandez"). Debe coincidir con
     * normalizarTexto() del editor (partials/anonimizacion-editor-js).
     */
    public static function normalizar(string $texto): string
    {
        $texto = \Normalizer::normalize($texto, \Normalizer::FORM_D);
        $texto = preg_replace('/\p{Mn}+/u', '', $texto);
        $texto = mb_strtolower(str_replace(['*', '_'], '', $texto));
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $texto));
    }

    /**
     * Recorta un span detectado: corta en el primer salto de linea (el NER a
     * veces une un nombre con el rotulo de hablante del parrafo siguiente, ej.
     * "Doña Selmira\n\nEdo") y quita puntuacion/espacios de los extremos.
     * Devuelve [texto, inicio, fin] o null si no queda nada util.
     */
    public static function recortarSpan(string $texto, int $inicio): ?array
    {
        $texto = preg_split('/\R/u', $texto)[0];
        if (!preg_match('/^([^\p{L}\p{N}]*)(.*?)[^\p{L}\p{N}]*$/us', $texto, $m) || $m[2] === '') {
            return null;
        }
        $inicio += mb_strlen($m[1]);
        return [$m[2], $inicio, $inicio + mb_strlen($m[2])];
    }
}
