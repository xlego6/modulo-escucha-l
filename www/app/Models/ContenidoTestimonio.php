<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContenidoTestimonio extends Model
{
    protected $table = 'esclarecimiento.contenido_testimonio';
    protected $primaryKey = 'id_contenido';

    protected $fillable = [
        'id_e_ind_fvt',
        'fecha_hechos_inicial',
        'fecha_hechos_final',
        'responsables_individuales',
        'temas_abordados',
        'otras_poblaciones_mencionadas',
        'otras_ocupaciones_mencionadas',
        'detalle_grupos_etnicos',
        'otros_hechos_victimizantes',
        'detalle_resistencias',
    ];

    public function rel_entrevista()
    {
        return $this->belongsTo(Entrevista::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    // Relaciones múltiples
    public function rel_poblaciones()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_poblacion', 'id_e_ind_fvt', 'id_poblacion', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_ocupaciones()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_ocupacion', 'id_e_ind_fvt', 'id_ocupacion', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_sexos()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_sexo', 'id_e_ind_fvt', 'id_sexo', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_identidades_genero()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_identidad_genero', 'id_e_ind_fvt', 'id_identidad', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_orientaciones_sexuales()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_orientacion_sexual', 'id_e_ind_fvt', 'id_orientacion', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_etnias()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_etnia', 'id_e_ind_fvt', 'id_etnia', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_rangos_etarios()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_rango_etario', 'id_e_ind_fvt', 'id_rango', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_discapacidades()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_discapacidad', 'id_e_ind_fvt', 'id_discapacidad', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_hechos_victimizantes()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_hecho_victimizante', 'id_e_ind_fvt', 'id_hecho', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_responsables()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_responsable', 'id_e_ind_fvt', 'id_responsable', 'id_e_ind_fvt', 'id_item');
    }

    public function rel_practicas_resistencia()
    {
        return $this->belongsToMany(CatItem::class, 'esclarecimiento.contenido_practica_resistencia', 'id_e_ind_fvt', 'id_practica', 'id_e_ind_fvt', 'id_item');
    }
}
