<?php

namespace App\Http\Controllers;

use App\Models\Entrevista;
use App\Models\Entrevistador;
use App\Models\Persona;
use App\Models\PersonaEntrevistada;
use App\Models\ConsentimientoInformado;
use App\Models\ContenidoTestimonio;
use App\Models\Geo;
use App\Models\CatItem;
use App\Models\TrazaActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EntrevistaWizardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar formulario de creación (wizard)
     */
    public function create()
    {
        $user = Auth::user();
        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if (!$entrevistador) {
            flash('No tiene perfil de entrevistador asignado.')->error();
            return redirect()->route('entrevistas.index');
        }

        $catalogos = $this->getCatalogos();
        $siguiente_numero = $this->calcularSiguienteNumero($entrevistador->id_entrevistador);

        return view('entrevistas.wizard.create', compact('entrevistador', 'catalogos', 'siguiente_numero'));
    }

    /**
     * Mostrar formulario de edición (wizard)
     */
    public function edit($id)
    {
        $entrevista = Entrevista::with([
            'rel_formatos',
            'rel_modalidades',
            'rel_necesidades_reparacion',
            'rel_personas_entrevistadas.rel_persona.rel_poblaciones',
            'rel_personas_entrevistadas.rel_persona.rel_ocupaciones',
            'rel_personas_entrevistadas.rel_consentimiento',
            'rel_contenido.rel_poblaciones',
            'rel_contenido.rel_ocupaciones',
            'rel_contenido.rel_sexos',
            'rel_contenido.rel_identidades_genero',
            'rel_contenido.rel_orientaciones_sexuales',
            'rel_contenido.rel_etnias',
            'rel_contenido.rel_rangos_etarios',
            'rel_contenido.rel_discapacidades',
            'rel_contenido.rel_hechos_victimizantes',
            'rel_contenido.rel_responsables',
        ])->findOrFail($id);

        $user = Auth::user();

        // Verificar permisos
        if (!$this->puedeEditar($user, $entrevista)) {
            flash('No tiene permisos para editar esta entrevista.')->error();
            return redirect()->route('entrevistas.index');
        }

        $catalogos = $this->getCatalogos();

        return view('entrevistas.wizard.edit', compact('entrevista', 'catalogos'));
    }

    /**
     * Guardar Paso 1: Testimoniales
     */
    public function storePaso1(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:500',
            'id_dependencia_origen' => 'required|integer',
            'id_tipo_testimonio' => 'required|integer',
            'formatos' => 'required|array|min:1',
            'num_testimoniantes' => 'required|integer|min:1|max:20',
            'id_territorio' => 'required|integer',
            'entrevista_lugar' => 'required|integer',
            'modalidades' => 'required|array|min:1',
            'fecha_toma_inicial' => 'required|date',
            'fecha_toma_final' => 'required|date|after_or_equal:fecha_toma_inicial',
            'id_idioma' => 'required|integer',
            'tiene_anexos' => 'required|in:0,1',
        ]);

        $user = Auth::user();
        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if (!$entrevistador) {
            return response()->json(['success' => false, 'message' => 'No tiene perfil de entrevistador asignado.'], 400);
        }

        DB::beginTransaction();
        try {
            $id_entrevista = $request->id_e_ind_fvt;

            if ($id_entrevista) {
                // Actualizar existente
                $entrevista = Entrevista::findOrFail($id_entrevista);
                $entrevista->update($this->getPaso1Data($request, $entrevistador));
            } else {
                // Crear nueva
                $siguiente_numero = $this->calcularSiguienteNumero($entrevistador->id_entrevistador);
                $codigo = $this->generarCodigo($entrevistador, $siguiente_numero, $request->id_dependencia_origen);
                $correlativo = $this->calcularCorrelativo();

                $data = $this->getPaso1Data($request, $entrevistador);
                $data['entrevista_codigo'] = $codigo;
                $data['entrevista_numero'] = $siguiente_numero;
                $data['entrevista_correlativo'] = $correlativo;
                $data['id_entrevistador'] = $entrevistador->id_entrevistador;
                $data['id_macroterritorio'] = $entrevistador->id_macroterritorio;
                $data['numero_entrevistador'] = $entrevistador->numero_entrevistador;
                $data['id_activo'] = 1;
                $data['id_subserie'] = 1;

                $entrevista = Entrevista::create($data);
                $id_entrevista = $entrevista->id_e_ind_fvt;
            }

            // Guardar relaciones múltiples
            $this->syncFormatos($entrevista, $request->formatos);
            $this->syncModalidades($entrevista, $request->modalidades);
            $this->syncNecesidadesReparacion($entrevista, $request->necesidades_reparacion ?? []);
            $this->syncAreasCompatibles($entrevista, $request->areas_compatibles ?? []);

            // Registrar traza
            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => $user->id,
                'accion' => $request->id_e_ind_fvt ? 'editar' : 'crear',
                'objeto' => 'entrevista',
                'id_registro' => $id_entrevista,
                'codigo' => $entrevista->entrevista_codigo,
                'referencia' => 'Paso 1: Datos Testimoniales - ' . $entrevista->titulo,
                'ip' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'id_e_ind_fvt' => $id_entrevista,
                'message' => 'Paso 1 guardado correctamente.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Guardar Paso 2: Testimoniantes
     */
    public function storePaso2(Request $request)
    {
        $request->validate([
            'id_e_ind_fvt' => 'required|integer',
            'testimoniantes' => 'required|array|min:1',
        ]);

        // Verificar que la entrevista existe
        if (!Entrevista::where('id_e_ind_fvt', $request->id_e_ind_fvt)->exists()) {
            return response()->json(['success' => false, 'message' => 'La entrevista no existe.'], 404);
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $entrevista = Entrevista::findOrFail($request->id_e_ind_fvt);

            // Obtener IDs de persona_entrevistada existentes
            $existentes = PersonaEntrevistada::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
                ->pluck('id_persona_entrevistada')
                ->toArray();

            $procesados = [];

            foreach ($request->testimoniantes as $index => $datos) {
                // Crear o actualizar persona
                $personaData = [
                    'nombre' => $datos['nombre'] ?? '',
                    'apellido' => $datos['apellido'] ?? '',
                    'nombre_identitario' => $datos['nombre_identitario'] ?? null,
                    'id_lugar_nacimiento_depto' => $datos['id_lugar_origen_depto'] ?? null,
                    'id_lugar_nacimiento' => $datos['id_lugar_origen_muni'] ?? null,
                    'id_sexo' => $datos['id_sexo'] ?? null,
                    'id_identidad' => $datos['id_identidad_genero'] ?? null,
                    'id_orientacion' => $datos['id_orientacion_sexual'] ?? null,
                    'id_etnia' => $datos['id_etnia'] ?? null,
                    'id_rango_etario' => $datos['id_rango_etario'] ?? null,
                    'id_discapacidad' => $datos['id_discapacidad'] ?? null,
                ];

                if (!empty($datos['id_persona'])) {
                    $persona = Persona::findOrFail($datos['id_persona']);
                    $persona->update($personaData);
                } else {
                    $persona = Persona::create($personaData);
                }

                // Sync poblaciones y ocupaciones
                $this->syncPersonaPoblaciones($persona, $datos['poblaciones'] ?? []);
                $this->syncPersonaOcupaciones($persona, $datos['ocupaciones'] ?? []);

                // Crear o actualizar persona_entrevistada
                if (!empty($datos['id_persona_entrevistada'])) {
                    $pe = PersonaEntrevistada::findOrFail($datos['id_persona_entrevistada']);
                    $pe->update([
                        'id_persona' => $persona->id_persona,
                        'edad' => $datos['edad'] ?? null,
                    ]);
                } else {
                    $pe = PersonaEntrevistada::create([
                        'id_persona' => $persona->id_persona,
                        'id_e_ind_fvt' => $entrevista->id_e_ind_fvt,
                        'edad' => $datos['edad'] ?? null,
                    ]);
                }

                $procesados[] = $pe->id_persona_entrevistada;

                // Guardar consentimiento informado
                $consentimientoData = [
                    'id_persona_entrevistada' => $pe->id_persona_entrevistada,
                    'tiene_documento_autorizacion' => $datos['consentimiento']['tiene_documento'] ?? 0,
                    'es_menor_edad' => $datos['consentimiento']['es_menor_edad'] ?? 0,
                    'autoriza_ser_entrevistado' => $datos['consentimiento']['autoriza_entrevista'] ?? 0,
                    'permite_grabacion' => $datos['consentimiento']['permite_grabacion'] ?? 0,
                    'permite_procesamiento_misional' => $datos['consentimiento']['permite_procesamiento'] ?? 0,
                    'permite_uso_conservacion_consulta' => $datos['consentimiento']['permite_uso'] ?? 0,
                    'considera_riesgo_seguridad' => $datos['consentimiento']['considera_riesgo'] ?? 0,
                    'autoriza_datos_personales_sin_anonimizar' => $datos['consentimiento']['autoriza_datos_personales'] ?? 0,
                    'autoriza_datos_sensibles_sin_anonimizar' => $datos['consentimiento']['autoriza_datos_sensibles'] ?? 0,
                    'observaciones' => $datos['consentimiento']['observaciones'] ?? null,
                ];

                ConsentimientoInformado::updateOrCreate(
                    ['id_persona_entrevistada' => $pe->id_persona_entrevistada],
                    $consentimientoData
                );
            }

            // Eliminar los que no fueron procesados
            $eliminar = array_diff($existentes, $procesados);
            if (!empty($eliminar)) {
                ConsentimientoInformado::whereIn('id_persona_entrevistada', $eliminar)->delete();
                PersonaEntrevistada::whereIn('id_persona_entrevistada', $eliminar)->delete();
            }

            // Registrar traza
            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => $user->id,
                'accion' => 'editar',
                'objeto' => 'entrevista',
                'id_registro' => $entrevista->id_e_ind_fvt,
                'codigo' => $entrevista->entrevista_codigo,
                'referencia' => 'Paso 2: Testimoniantes - ' . count($request->testimoniantes) . ' persona(s)',
                'ip' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paso 2 guardado correctamente.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Guardar Paso 3: Contenido del testimonio
     */
    public function storePaso3(Request $request)
    {
        $request->validate([
            'id_e_ind_fvt' => 'required|integer',
            'fecha_hechos_inicial' => 'required|date',
            'fecha_hechos_final' => 'required|date|after_or_equal:fecha_hechos_inicial',
            'temas_abordados' => 'required|string',
        ]);

        // Verificar que la entrevista existe
        $entrevista = Entrevista::find($request->id_e_ind_fvt);
        if (!$entrevista) {
            return response()->json(['success' => false, 'message' => 'La entrevista no existe.'], 404);
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {

            // Actualizar fechas en entrevista principal
            $entrevista->update([
                'hechos_del' => $request->fecha_hechos_inicial,
                'hechos_al' => $request->fecha_hechos_final,
            ]);

            // Guardar contenido
            $contenido = ContenidoTestimonio::updateOrCreate(
                ['id_e_ind_fvt' => $entrevista->id_e_ind_fvt],
                [
                    'fecha_hechos_inicial' => $request->fecha_hechos_inicial,
                    'fecha_hechos_final' => $request->fecha_hechos_final,
                    'responsables_individuales' => $request->responsables_individuales,
                    'temas_abordados' => $request->temas_abordados,
                ]
            );

            // Sync relaciones múltiples de contenido
            $this->syncContenidoRelaciones($entrevista->id_e_ind_fvt, $request);

            // Registrar traza
            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => $user->id,
                'accion' => 'editar',
                'objeto' => 'entrevista',
                'id_registro' => $entrevista->id_e_ind_fvt,
                'codigo' => $entrevista->entrevista_codigo,
                'referencia' => 'Paso 3: Contenido del Testimonio',
                'ip' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrevista guardada completamente.',
                'redirect' => route('entrevistas.show', $entrevista->id_e_ind_fvt)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener datos para Paso 1
     */
    private function getPaso1Data($request, $entrevistador)
    {
        return [
            'titulo' => $request->titulo,
            'id_dependencia_origen' => $request->id_dependencia_origen,
            'id_equipo_estrategia' => $request->id_equipo_estrategia,
            'nombre_proyecto' => $request->nombre_proyecto,
            'id_tipo_testimonio' => $request->id_tipo_testimonio,
            'num_testimoniantes' => $request->num_testimoniantes,
            'id_territorio' => $request->id_territorio,
            'entrevista_lugar' => $request->entrevista_lugar,
            'fecha_toma_inicial' => $request->fecha_toma_inicial,
            'fecha_toma_final' => $request->fecha_toma_final,
            'entrevista_fecha' => $request->fecha_toma_inicial,
            'id_idioma' => $request->id_idioma,
            'tiene_anexos' => $request->tiene_anexos,
            'descripcion_anexos' => $request->descripcion_anexos,
            'observaciones_toma' => $request->observaciones_toma,
        ];
    }

    /**
     * Sync formatos
     */
    private function syncFormatos($entrevista, $formatos)
    {
        DB::table('esclarecimiento.entrevista_formato')
            ->where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->delete();

        foreach ($formatos as $id_formato) {
            DB::table('esclarecimiento.entrevista_formato')->insert([
                'id_e_ind_fvt' => $entrevista->id_e_ind_fvt,
                'id_formato' => $id_formato,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Sync modalidades
     */
    private function syncModalidades($entrevista, $modalidades)
    {
        DB::table('esclarecimiento.entrevista_modalidad')
            ->where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->delete();

        foreach ($modalidades as $id_modalidad) {
            DB::table('esclarecimiento.entrevista_modalidad')->insert([
                'id_e_ind_fvt' => $entrevista->id_e_ind_fvt,
                'id_modalidad' => $id_modalidad,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Sync necesidades reparación
     */
    private function syncNecesidadesReparacion($entrevista, $necesidades)
    {
        DB::table('esclarecimiento.entrevista_necesidad_reparacion')
            ->where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->delete();

        foreach ($necesidades as $id_necesidad) {
            DB::table('esclarecimiento.entrevista_necesidad_reparacion')->insert([
                'id_e_ind_fvt' => $entrevista->id_e_ind_fvt,
                'id_necesidad' => $id_necesidad,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Sync áreas compatibles
     */
    private function syncAreasCompatibles($entrevista, $areas)
    {
        DB::table('esclarecimiento.entrevista_area_compatible')
            ->where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->delete();

        foreach ($areas as $id_area) {
            DB::table('esclarecimiento.entrevista_area_compatible')->insert([
                'id_e_ind_fvt' => $entrevista->id_e_ind_fvt,
                'id_area' => $id_area,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Sync poblaciones de persona
     */
    private function syncPersonaPoblaciones($persona, $poblaciones)
    {
        DB::table('fichas.persona_poblacion')
            ->where('id_persona', $persona->id_persona)
            ->delete();

        foreach ($poblaciones as $id_poblacion) {
            DB::table('fichas.persona_poblacion')->insert([
                'id_persona' => $persona->id_persona,
                'id_poblacion' => $id_poblacion,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Sync ocupaciones de persona
     */
    private function syncPersonaOcupaciones($persona, $ocupaciones)
    {
        DB::table('fichas.persona_ocupacion')
            ->where('id_persona', $persona->id_persona)
            ->delete();

        foreach ($ocupaciones as $id_ocupacion) {
            DB::table('fichas.persona_ocupacion')->insert([
                'id_persona' => $persona->id_persona,
                'id_ocupacion' => $id_ocupacion,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Sync relaciones de contenido
     */
    private function syncContenidoRelaciones($id_e_ind_fvt, $request)
    {
        $tablas = [
            'contenido_poblacion' => ['id_poblacion', $request->contenido_poblaciones ?? []],
            'contenido_ocupacion' => ['id_ocupacion', $request->contenido_ocupaciones ?? []],
            'contenido_sexo' => ['id_sexo', $request->contenido_sexos ?? []],
            'contenido_identidad_genero' => ['id_identidad', $request->contenido_identidades ?? []],
            'contenido_orientacion_sexual' => ['id_orientacion', $request->contenido_orientaciones ?? []],
            'contenido_etnia' => ['id_etnia', $request->contenido_etnias ?? []],
            'contenido_rango_etario' => ['id_rango', $request->contenido_rangos ?? []],
            'contenido_discapacidad' => ['id_discapacidad', $request->contenido_discapacidades ?? []],
            'contenido_hecho_victimizante' => ['id_hecho', $request->contenido_hechos ?? []],
            'contenido_responsable' => ['id_responsable', $request->contenido_responsables ?? []],
        ];

        foreach ($tablas as $tabla => [$campo, $valores]) {
            DB::table("esclarecimiento.{$tabla}")
                ->where('id_e_ind_fvt', $id_e_ind_fvt)
                ->delete();

            foreach ($valores as $valor) {
                DB::table("esclarecimiento.{$tabla}")->insert([
                    'id_e_ind_fvt' => $id_e_ind_fvt,
                    $campo => $valor,
                ]);
            }
        }

        // Guardar lugares mencionados
        $this->syncLugaresMencionados($id_e_ind_fvt, $request->contenido_lugares ?? []);
    }

    /**
     * Sync lugares mencionados en el testimonio
     */
    private function syncLugaresMencionados($id_e_ind_fvt, $lugares)
    {
        DB::table('esclarecimiento.contenido_lugar')
            ->where('id_e_ind_fvt', $id_e_ind_fvt)
            ->delete();

        foreach ($lugares as $lugar) {
            if (!empty($lugar['id_departamento']) || !empty($lugar['id_municipio'])) {
                DB::table('esclarecimiento.contenido_lugar')->insert([
                    'id_e_ind_fvt' => $id_e_ind_fvt,
                    'id_departamento' => $lugar['id_departamento'] ?? null,
                    'id_municipio' => $lugar['id_municipio'] ?? null,
                ]);
            }
        }
    }

    /**
     * Obtener todos los catálogos necesarios
     */
    private function getCatalogos()
    {
        // Obtener equipos/estrategias con su dependencia padre (guardada en campo 'otro')
        // Solo items habilitados
        $equipos_raw = CatItem::where('id_cat', 18)
            ->where('habilitado', 1)
            ->orderBy('orden')
            ->get();

        // IDs de dependencias que son "otras" (Estrategias, Dirección General, etc.)
        // Estas son las que muestran las opciones con otro='otros'
        $dependencias_otras = [34, 35, 36, 37, 38, 39, 40];

        $equipos_por_dependencia = [];
        $equipos_otros = []; // Para guardar temporalmente los de 'otros'

        foreach ($equipos_raw as $equipo) {
            $dep_id = $equipo->otro; // id_item de la dependencia padre o 'otros'

            if ($dep_id === 'otros') {
                // Guardar para asignar a múltiples dependencias
                $equipos_otros[$equipo->id_item] = $equipo->descripcion;
            } else {
                if (!isset($equipos_por_dependencia[$dep_id])) {
                    $equipos_por_dependencia[$dep_id] = [];
                }
                $equipos_por_dependencia[$dep_id][$equipo->id_item] = $equipo->descripcion;
            }
        }

        // Asignar equipos 'otros' a cada dependencia que corresponde
        foreach ($dependencias_otras as $dep_id) {
            if (!isset($equipos_por_dependencia[$dep_id])) {
                $equipos_por_dependencia[$dep_id] = [];
            }
            $equipos_por_dependencia[$dep_id] = array_replace($equipos_por_dependencia[$dep_id], $equipos_otros);
        }

        return [
            'dependencias' => CatItem::where('id_cat', 4)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'equipos_estrategias' => $equipos_por_dependencia, // Array por dependencia
            'tipos_testimonio' => CatItem::where('id_cat', 5)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'formatos' => CatItem::where('id_cat', 6)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'modalidades' => CatItem::where('id_cat', 7)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'idiomas' => CatItem::where('id_cat', 8)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'poblaciones' => CatItem::where('id_cat', 9)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'hechos_victimizantes' => CatItem::where('id_cat', 10)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'ocupaciones' => CatItem::where('id_cat', 11)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'identidades_genero' => CatItem::where('id_cat', 12)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'orientaciones_sexuales' => CatItem::where('id_cat', 13)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'rangos_etarios' => CatItem::where('id_cat', 14)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'discapacidades' => CatItem::where('id_cat', 15)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'necesidades_reparacion' => CatItem::where('id_cat', 16)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'responsables_colectivos' => CatItem::where('id_cat', 17)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'sexos' => CatItem::where('id_cat', 1)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'etnias' => CatItem::where('id_cat', 3)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'departamentos' => Geo::where('nivel', 2)->orderBy('descripcion')->pluck('descripcion', 'id_geo'),
        ];
    }

    /**
     * Calcular siguiente número de entrevista
     */
    private function calcularSiguienteNumero($id_entrevistador)
    {
        $ultimo = Entrevista::where('id_entrevistador', $id_entrevistador)
            ->where('id_activo', 1)
            ->max('entrevista_numero');
        return ($ultimo ?? 0) + 1;
    }

    /**
     * Generar código de entrevista según Dependencia de Origen
     * Formato: PREFIJO-NNNN-NNN
     * Prefijos según dependencia:
     * - DMMC: Dirección Museo de Memoria y Conflicto
     * - DCMH: Dirección de Construcción de Memoria Histórica
     * - DAV: Dirección de Acuerdos de la Verdad
     * - DADH: Dirección de Archivo de los Derechos Humanos
     * - TRA: Estrategias (Comunicaciones, General, Pedagogía, Enfoques, Psicosocial, Territorialización)
     */
    private function generarCodigo($entrevistador, $numero, $id_dependencia_origen = null)
    {
        // Obtener prefijo según dependencia de origen
        $prefijo = $this->obtenerPrefijoCodigo($id_dependencia_origen);

        $num_ent = str_pad($entrevistador->numero_entrevistador ?? 0, 4, '0', STR_PAD_LEFT);
        $num_entr = str_pad($numero, 3, '0', STR_PAD_LEFT);
        return $prefijo . '-' . $num_ent . '-' . $num_entr;
    }

    /**
     * Obtener prefijo de código según dependencia de origen
     */
    private function obtenerPrefijoCodigo($id_dependencia_origen)
    {
        if (!$id_dependencia_origen) {
            return 'TES'; // Default si no hay dependencia
        }

        // Buscar el abreviado de la dependencia
        $dependencia = CatItem::find($id_dependencia_origen);

        if ($dependencia && $dependencia->abreviado) {
            return $dependencia->abreviado;
        }

        return 'TES';
    }

    /**
     * Calcular correlativo global
     */
    private function calcularCorrelativo()
    {
        return Entrevista::max('entrevista_correlativo') + 1;
    }

    /**
     * Verificar si puede editar
     */
    private function puedeEditar($user, $entrevista)
    {
        if ($user->id_nivel <= 2) {
            return true;
        }
        if ($entrevista->rel_entrevistador && $entrevista->rel_entrevistador->id_usuario == $user->id) {
            return true;
        }
        return false;
    }
}
