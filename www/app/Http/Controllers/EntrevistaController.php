<?php

namespace App\Http\Controllers;

use App\Models\Entrevista;
use App\Models\Entrevistador;
use App\Models\Permiso;
use App\Models\Geo;
use App\Models\CatItem;
use App\Models\TrazaActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EntrevistaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Listado de entrevistas
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Entrevista::where('id_activo', 1);

        // Entrevistador (nivel 3): solo ve sus propias entrevistas o las que tiene permiso
        if ($user->id_nivel == 3) {
            $query->where(function($q) use ($user) {
                // Sus propias entrevistas
                $q->where('id_entrevistador', $user->id_entrevistador)
                  // O entrevistas con permiso otorgado
                  ->orWhereIn('id_e_ind_fvt', function($subquery) use ($user) {
                      $subquery->select('id_e_ind_fvt')
                          ->from('esclarecimiento.permiso')
                          ->where('id_entrevistador', $user->id_entrevistador)
                          ->where(function($pq) {
                              $pq->whereNull('fecha_vencimiento')
                                 ->orWhere('fecha_vencimiento', '>', now());
                          });
                  });
            });
        }

        // Filtros
        if ($request->filled('codigo')) {
            $query->where('entrevista_codigo', 'ILIKE', '%' . $request->codigo . '%');
        }

        if ($request->filled('titulo')) {
            $query->where('titulo', 'ILIKE', '%' . $request->titulo . '%');
        }

        if ($request->filled('fecha_desde')) {
            $query->where('entrevista_fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('entrevista_fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('id_entrevistador')) {
            $query->where('id_entrevistador', $request->id_entrevistador);
        }

        $entrevistas = $query->with(['rel_entrevistador', 'rel_entrevistador.rel_usuario'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Entrevistador (nivel 3): no mostrar filtro de entrevistadores
        $entrevistadores = collect();
        if ($user->id_nivel <= 2) {
            $entrevistadores = Entrevistador::with('rel_usuario')
                ->orderBy('numero_entrevistador')
                ->get()
                ->pluck('rel_usuario.name', 'id_entrevistador')
                ->prepend('-- Todos --', '');
        }

        return view('entrevistas.index', compact('entrevistas', 'entrevistadores'));
    }

    /**
     * Formulario para crear nueva entrevista
     */
    public function create()
    {
        $user = Auth::user();

        // Obtener el entrevistador del usuario actual
        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if (!$entrevistador) {
            flash('No tiene perfil de entrevistador asignado.')->error();
            return redirect()->route('entrevistas.index');
        }

        // Calcular siguiente número de entrevista
        $siguiente_numero = $this->calcularSiguienteNumero($entrevistador->id_entrevistador);

        // Listas para selects
        $territorios = Geo::where('nivel', 2)->orderBy('descripcion')->pluck('descripcion', 'id_geo')->prepend('-- Seleccione --', '');
        $municipios = Geo::where('nivel', 3)->orderBy('descripcion')->pluck('descripcion', 'id_geo')->prepend('-- Seleccione --', '');

        return view('entrevistas.create', compact(
            'entrevistador',
            'siguiente_numero',
            'territorios',
            'municipios'
        ));
    }

    /**
     * Almacenar nueva entrevista
     */
    public function store(Request $request)
    {
        $request->validate([
            'entrevista_numero' => 'required|integer|min:1',
            'entrevista_fecha' => 'required|date',
            'titulo' => 'required|string|max:500',
            'tiempo_entrevista' => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();
        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if (!$entrevistador) {
            flash('No tiene perfil de entrevistador asignado.')->error();
            return redirect()->route('entrevistas.index');
        }

        // Verificar que el número no esté duplicado para este entrevistador
        $existe = Entrevista::where('id_entrevistador', $entrevistador->id_entrevistador)
            ->where('entrevista_numero', $request->entrevista_numero)
            ->where('id_activo', 1)
            ->exists();

        if ($existe) {
            flash('Ya existe una entrevista con ese número para este entrevistador.')->error();
            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            // Generar código
            $codigo = $this->generarCodigo($entrevistador, $request->entrevista_numero);
            $correlativo = $this->calcularCorrelativo();

            $entrevista = Entrevista::create([
                'id_subserie' => 1, // Tipo por defecto (VI - Víctimas)
                'id_entrevistador' => $entrevistador->id_entrevistador,
                'id_macroterritorio' => $entrevistador->id_macroterritorio,
                'id_territorio' => $request->id_territorio ?: $entrevistador->id_territorio,
                'entrevista_codigo' => $codigo,
                'entrevista_numero' => $request->entrevista_numero,
                'entrevista_correlativo' => $correlativo,
                'entrevista_fecha' => $request->entrevista_fecha,
                'numero_entrevistador' => $entrevistador->numero_entrevistador,
                'hechos_del' => $request->hechos_del,
                'hechos_al' => $request->hechos_al,
                'hechos_lugar' => $request->hechos_lugar,
                'entrevista_lugar' => $request->entrevista_lugar,
                'titulo' => $request->titulo,
                'anotaciones' => $request->anotaciones,
                'tiempo_entrevista' => $request->tiempo_entrevista,
                'es_virtual' => $request->es_virtual ?? 0,
                'nna' => $request->nna ?? 0,
                'id_etnico' => $request->id_etnico,
                'id_activo' => 1,
            ]);

            // Registrar traza
            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => $user->id,
                'accion' => 'crear',
                'objeto' => 'entrevista',
                'id_registro' => $entrevista->id_e_ind_fvt,
                'codigo' => $codigo,
                'referencia' => 'Creacion de entrevista: ' . $entrevista->titulo,
                'ip' => $request->ip(),
            ]);

            DB::commit();
            flash('Entrevista creada exitosamente.')->success();
            return redirect()->route('entrevistas.show', $entrevista->id_e_ind_fvt);

        } catch (\Exception $e) {
            DB::rollBack();
            flash('Error al crear la entrevista: ' . $e->getMessage())->error();
            return back()->withInput();
        }
    }

    /**
     * Ver detalle de entrevista
     */
    public function show($id)
    {
        $user = Auth::user();
        $entrevista = Entrevista::with([
            'rel_entrevistador',
            'rel_entrevistador.rel_usuario',
            'rel_lugar_entrevista',
            'rel_lugar_hechos',
            'rel_adjuntos',
            'rel_personas_entrevistadas',
            'rel_personas_entrevistadas.rel_persona',
            'rel_personas_entrevistadas.rel_persona.rel_sexo',
            'rel_personas_entrevistadas.rel_persona.rel_etnia',
            'rel_personas_entrevistadas.rel_persona.rel_orientacion',
            'rel_personas_entrevistadas.rel_persona.rel_identidad',
            'rel_personas_entrevistadas.rel_persona.rel_rango_etario',
            'rel_personas_entrevistadas.rel_persona.rel_discapacidad',
            'rel_personas_entrevistadas.rel_persona.rel_poblaciones',
            'rel_personas_entrevistadas.rel_persona.rel_ocupaciones',
            'rel_personas_entrevistadas.rel_persona.rel_lugar_nacimiento',
            'rel_personas_entrevistadas.rel_consentimiento',
            'rel_dependencia_origen',
            'rel_tipo_testimonio',
            'rel_idioma',
            'rel_formatos',
            'rel_modalidades',
            'rel_necesidades_reparacion',
            'rel_contenido',
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

        // Verificar permisos de visualización
        if (!$this->puedeVer($user, $entrevista)) {
            flash('No tiene permisos para ver esta entrevista.')->error();
            return redirect()->route('entrevistas.index');
        }

        // Cargar lugares
        $depto_toma = $entrevista->id_territorio ? Geo::find($entrevista->id_territorio) : null;
        $muni_toma = $entrevista->entrevista_lugar ? Geo::find($entrevista->entrevista_lugar) : null;

        // Cargar áreas compatibles
        $areas_compatibles = DB::table('esclarecimiento.entrevista_area_compatible')
            ->join('catalogos.cat_item', 'entrevista_area_compatible.id_area', '=', 'cat_item.id_item')
            ->where('id_e_ind_fvt', $id)
            ->pluck('cat_item.descripcion');

        // Cargar lugares mencionados en el testimonio
        $lugares_mencionados = DB::table('esclarecimiento.contenido_lugar')
            ->leftJoin('catalogos.geo as depto', 'contenido_lugar.id_departamento', '=', 'depto.id_geo')
            ->leftJoin('catalogos.geo as muni', 'contenido_lugar.id_municipio', '=', 'muni.id_geo')
            ->where('contenido_lugar.id_e_ind_fvt', $id)
            ->select(
                'depto.descripcion as departamento',
                'muni.descripcion as municipio'
            )
            ->get();

        return view('entrevistas.show', compact('entrevista', 'depto_toma', 'muni_toma', 'areas_compatibles', 'lugares_mencionados'));
    }

    /**
     * Formulario para editar entrevista
     */
    public function edit($id)
    {
        $entrevista = Entrevista::with(['rel_entrevistador'])->findOrFail($id);

        $user = Auth::user();

        // Verificar permisos
        if (!$this->puedeEditar($user, $entrevista)) {
            flash('No tiene permisos para editar esta entrevista.')->error();
            return redirect()->route('entrevistas.index');
        }

        $territorios = Geo::where('nivel', 2)->orderBy('descripcion')->pluck('descripcion', 'id_geo')->prepend('-- Seleccione --', '');
        $municipios = Geo::where('nivel', 3)->orderBy('descripcion')->pluck('descripcion', 'id_geo')->prepend('-- Seleccione --', '');

        return view('entrevistas.edit', compact('entrevista', 'territorios', 'municipios'));
    }

    /**
     * Actualizar entrevista
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'entrevista_fecha' => 'required|date',
            'titulo' => 'required|string|max:500',
            'tiempo_entrevista' => 'nullable|integer|min:1',
        ]);

        $entrevista = Entrevista::with(['rel_entrevistador'])->findOrFail($id);
        $user = Auth::user();

        // Verificar permisos
        if (!$this->puedeEditar($user, $entrevista)) {
            flash('No tiene permisos para editar esta entrevista.')->error();
            return redirect()->route('entrevistas.index');
        }

        DB::beginTransaction();
        try {
            $entrevista->update([
                'id_territorio' => $request->id_territorio,
                'entrevista_fecha' => $request->entrevista_fecha,
                'hechos_del' => $request->hechos_del,
                'hechos_al' => $request->hechos_al,
                'hechos_lugar' => $request->hechos_lugar,
                'entrevista_lugar' => $request->entrevista_lugar,
                'titulo' => $request->titulo,
                'anotaciones' => $request->anotaciones,
                'tiempo_entrevista' => $request->tiempo_entrevista,
                'es_virtual' => $request->es_virtual ?? 0,
                'nna' => $request->nna ?? 0,
                'id_etnico' => $request->id_etnico,
            ]);

            // Registrar traza
            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => $user->id,
                'accion' => 'editar',
                'objeto' => 'entrevista',
                'id_registro' => $entrevista->id_e_ind_fvt,
                'codigo' => $entrevista->entrevista_codigo,
                'referencia' => 'Edicion de entrevista: ' . $entrevista->titulo,
                'ip' => $request->ip(),
            ]);

            DB::commit();
            flash('Entrevista actualizada exitosamente.')->success();
            return redirect()->route('entrevistas.show', $entrevista->id_e_ind_fvt);

        } catch (\Exception $e) {
            DB::rollBack();
            flash('Error al actualizar la entrevista: ' . $e->getMessage())->error();
            return back()->withInput();
        }
    }

    /**
     * Eliminar entrevista (soft delete)
     */
    public function destroy($id)
    {
        $entrevista = Entrevista::findOrFail($id);
        $user = Auth::user();

        // Solo admin puede eliminar
        if ($user->id_nivel > 1) {
            flash('No tiene permisos para eliminar entrevistas.')->error();
            return redirect()->route('entrevistas.index');
        }

        $entrevista->update(['id_activo' => 0]);

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'eliminar',
            'objeto' => 'entrevista',
            'id_registro' => $entrevista->id_e_ind_fvt,
            'codigo' => $entrevista->entrevista_codigo,
            'referencia' => 'Eliminacion de entrevista: ' . $entrevista->titulo,
            'ip' => request()->ip(),
        ]);

        flash('Entrevista eliminada exitosamente.')->success();
        return redirect()->route('entrevistas.index');
    }

    /**
     * Calcular siguiente número de entrevista para un entrevistador
     */
    private function calcularSiguienteNumero($id_entrevistador)
    {
        $ultimo = Entrevista::where('id_entrevistador', $id_entrevistador)
            ->where('id_activo', 1)
            ->max('entrevista_numero');

        return ($ultimo ?? 0) + 1;
    }

    /**
     * Generar código de entrevista
     */
    private function generarCodigo($entrevistador, $numero)
    {
        $prefijo = 'VI'; // Víctimas por defecto
        $num_ent = str_pad($entrevistador->numero_entrevistador ?? 0, 4, '0', STR_PAD_LEFT);
        $num_entr = str_pad($numero, 3, '0', STR_PAD_LEFT);

        return $prefijo . '-' . $num_ent . '-' . $num_entr;
    }

    /**
     * Calcular correlativo global
     */
    private function calcularCorrelativo()
    {
        return Entrevista::max('entrevista_correlativo') + 1;
    }

    /**
     * Verificar si el usuario puede editar la entrevista
     */
    private function puedeEditar($user, $entrevista)
    {
        // Administradores y Esclarecimiento pueden editar todo
        if ($user->id_nivel <= 2) {
            return true;
        }

        // El entrevistador dueño puede editar su propia entrevista
        if ($entrevista->rel_entrevistador && $entrevista->rel_entrevistador->id_usuario == $user->id) {
            return true;
        }

        // Verificar si tiene permiso otorgado (tipo 2=Escritura o 3=Completo)
        $permiso = Permiso::where('id_entrevistador', $user->id_entrevistador)
            ->where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->where('id_tipo', '>=', 2) // Escritura o Completo
            ->where(function($q) {
                $q->whereNull('fecha_vencimiento')
                  ->orWhere('fecha_vencimiento', '>', now());
            })
            ->exists();

        return $permiso;
    }

    /**
     * Verificar si el usuario puede ver la entrevista
     */
    private function puedeVer($user, $entrevista)
    {
        // Administradores y Esclarecimiento pueden ver todo
        if ($user->id_nivel <= 2) {
            return true;
        }

        // El entrevistador dueño puede ver su propia entrevista
        if ($entrevista->rel_entrevistador && $entrevista->rel_entrevistador->id_usuario == $user->id) {
            return true;
        }

        // Verificar si tiene permiso otorgado (cualquier tipo)
        $permiso = Permiso::where('id_entrevistador', $user->id_entrevistador)
            ->where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->where(function($q) {
                $q->whereNull('fecha_vencimiento')
                  ->orWhere('fecha_vencimiento', '>', now());
            })
            ->exists();

        return $permiso;
    }
}
