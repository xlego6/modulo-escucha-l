<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consentimiento extends Model
{
    protected $table = 'fichas.entrevista';
    protected $primaryKey = 'id_entrevista';

    protected $fillable = [
        'id_e_ind_fvt',
        'id_idioma',
        'id_nativo',
        'nombre_interprete',
        'documentacion_aporta',
        'documentacion_especificar',
        'identifica_testigos',
        'ampliar_relato',
        'ampliar_relato_temas',
        'priorizar_entrevista',
        'priorizar_entrevista_asuntos',
        'contiene_patrones',
        'contiene_patrones_cuales',
        'indicaciones_transcripcion',
        'observaciones',
        'identificacion_consentimiento',
        'conceder_entrevista',
        'grabar_audio',
        'grabar_video',
        'tomar_fotografia',
        'elaborar_informe',
        'tratamiento_datos_analizar',
        'tratamiento_datos_analizar_sensible',
        'tratamiento_datos_utilizar',
        'tratamiento_datos_utilizar_sensible',
        'tratamiento_datos_publicar',
        'divulgar_material',
        'traslado_info',
        'compartir_info',
        'nombre_autoridad_etnica',
        'nombre_identitario',
        'pueblo_representado',
        'id_pueblo_representado',
        'consentimiento_nombres',
        'consentimiento_apellidos',
        'consentimiento_sexo',
    ];

    public function rel_entrevista() {
        return $this->belongsTo(Entrevista::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    public function rel_idioma() {
        return $this->belongsTo(CatItem::class, 'id_idioma', 'id_item');
    }

    public function getFmtConsentimientoCompletoAttribute() {
        $campos = [
            'conceder_entrevista',
            'grabar_audio',
            'tratamiento_datos_analizar',
            'tratamiento_datos_utilizar',
        ];

        foreach ($campos as $campo) {
            if ($this->$campo != 1) {
                return false;
            }
        }
        return true;
    }
}
