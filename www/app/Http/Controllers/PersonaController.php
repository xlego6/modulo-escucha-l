<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\CatItem;
use App\Models\Geo;
use App\Models\TrazaActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PersonaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Listado de personas
     */
    public function index(Request $request)
    {
        $query = Persona::query();

        // Filtros
        if ($request->filled('nombre')) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'ILIKE', '%' . $request->nombre . '%')
                  ->orWhere('apellido', 'ILIKE', '%' . $request->nombre . '%')
                  ->orWhere('nombre_identitario', 'ILIKE', '%' . $request->nombre . '%');
            });
        }

        if ($request->filled('documento')) {
            $query->where('num_documento', 'ILIKE', '%' . $request->documento . '%');
        }

        if ($request->filled('id_sexo')) {
            $query->where('id_sexo', $request->id_sexo);
        }

        if ($request->filled('id_etnia')) {
            $query->where('id_etnia', $request->id_etnia);
        }

        $personas = $query->with(['rel_sexo', 'rel_tipo_documento', 'rel_etnia'])
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(15);

        $sexos = CatItem::where('id_cat', 1)->orderBy('orden')->pluck('descripcion', 'id_item')->prepend('-- Todos --', '');
        $etnias = CatItem::where('id_cat', 3)->orderBy('orden')->pluck('descripcion', 'id_item')->prepend('-- Todos --', '');

        return view('personas.index', compact('personas', 'sexos', 'etnias'));
    }

    /**
     * Formulario para crear nueva persona
     */
    public function create()
    {
        $catalogos = $this->getCatalogos();
        return view('personas.create', $catalogos);
    }

    /**
     * Almacenar nueva persona
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:200',
            'apellido' => 'required|string|max:200',
        ]);

        $user = Auth::user();

        // Verificar documento duplicado
        if ($request->filled('num_documento')) {
            $existe = Persona::where('num_documento', $request->num_documento)
                ->where('id_tipo_documento', $request->id_tipo_documento)
                ->exists();

            if ($existe) {
                flash('Ya existe una persona con ese documento.')->error();
                return back()->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $persona = Persona::create($request->only([
                'nombre', 'apellido', 'alias', 'nombre_identitario',
                'fec_nac_a', 'fec_nac_m', 'fec_nac_d',
                'id_lugar_nacimiento', 'id_lugar_nacimiento_depto',
                'id_sexo', 'id_orientacion', 'id_identidad',
                'id_etnia', 'id_etnia_indigena',
                'id_tipo_documento', 'num_documento',
                'id_nacionalidad', 'id_estado_civil',
                'id_lugar_residencia', 'id_lugar_residencia_muni', 'id_lugar_residencia_depto',
                'id_zona', 'telefono', 'correo_electronico',
                'id_edu_formal', 'profesion', 'ocupacion_actual', 'id_ocupacion_actual',
                'id_rango_etario', 'id_discapacidad',
            ]));

            // Sync poblaciones y ocupaciones
            $this->syncPoblaciones($persona, $request->poblaciones ?? []);
            $this->syncOcupaciones($persona, $request->ocupaciones ?? []);

            TrazaActividad::create([
                'id_usuario' => $user->id,
                'accion' => 'crear_persona',
                'tabla' => 'persona',
                'id_registro' => $persona->id_persona,
                'descripcion' => 'Creacion de persona: ' . $persona->fmt_nombre_completo,
                'ip' => $request->ip(),
            ]);

            DB::commit();
            flash('Persona creada exitosamente.')->success();
            return redirect()->route('personas.show', $persona->id_persona);

        } catch (\Exception $e) {
            DB::rollBack();
            flash('Error al crear la persona: ' . $e->getMessage())->error();
            return back()->withInput();
        }
    }

    /**
     * Ver detalle de persona
     */
    public function show($id)
    {
        $persona = Persona::with([
            'rel_sexo',
            'rel_tipo_documento',
            'rel_etnia',
            'rel_lugar_nacimiento',
            'rel_lugar_residencia',
            'rel_orientacion',
            'rel_identidad',
            'rel_rango_etario',
            'rel_discapacidad',
            'rel_poblaciones',
            'rel_ocupaciones',
        ])->findOrFail($id);

        // Obtener entrevistas vinculadas con info adicional
        $entrevistas = DB::table('fichas.persona_entrevistada')
            ->join('esclarecimiento.e_ind_fvt', 'persona_entrevistada.id_e_ind_fvt', '=', 'e_ind_fvt.id_e_ind_fvt')
            ->where('persona_entrevistada.id_persona', $id)
            ->where('e_ind_fvt.id_activo', 1)
            ->select('e_ind_fvt.*', 'persona_entrevistada.id_persona_entrevistada', 'persona_entrevistada.edad')
            ->get();

        // Obtener departamento de origen si existe
        $departamento_origen = null;
        if ($persona->id_lugar_nacimiento_depto) {
            $departamento_origen = Geo::find($persona->id_lugar_nacimiento_depto);
        }

        return view('personas.show', compact('persona', 'entrevistas', 'departamento_origen'));
    }

    /**
     * Formulario para editar persona
     */
    public function edit($id)
    {
        $persona = Persona::with([
            'rel_poblaciones',
            'rel_ocupaciones',
        ])->findOrFail($id);

        $catalogos = $this->getCatalogos();

        return view('personas.edit', array_merge(['persona' => $persona], $catalogos));
    }

    /**
     * Actualizar persona
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:200',
            'apellido' => 'required|string|max:200',
        ]);

        $persona = Persona::findOrFail($id);
        $user = Auth::user();

        // Verificar documento duplicado (excluyendo el actual)
        if ($request->filled('num_documento')) {
            $existe = Persona::where('num_documento', $request->num_documento)
                ->where('id_tipo_documento', $request->id_tipo_documento)
                ->where('id_persona', '!=', $id)
                ->exists();

            if ($existe) {
                flash('Ya existe otra persona con ese documento.')->error();
                return back()->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $persona->update($request->only([
                'nombre', 'apellido', 'alias', 'nombre_identitario',
                'fec_nac_a', 'fec_nac_m', 'fec_nac_d',
                'id_lugar_nacimiento', 'id_lugar_nacimiento_depto',
                'id_sexo', 'id_orientacion', 'id_identidad',
                'id_etnia', 'id_etnia_indigena',
                'id_tipo_documento', 'num_documento',
                'id_nacionalidad', 'id_estado_civil',
                'id_lugar_residencia', 'id_lugar_residencia_muni', 'id_lugar_residencia_depto',
                'id_zona', 'telefono', 'correo_electronico',
                'id_edu_formal', 'profesion', 'ocupacion_actual', 'id_ocupacion_actual',
                'id_rango_etario', 'id_discapacidad',
            ]));

            // Sync poblaciones y ocupaciones
            $this->syncPoblaciones($persona, $request->poblaciones ?? []);
            $this->syncOcupaciones($persona, $request->ocupaciones ?? []);

            TrazaActividad::create([
                'id_usuario' => $user->id,
                'accion' => 'editar_persona',
                'tabla' => 'persona',
                'id_registro' => $persona->id_persona,
                'descripcion' => 'Edicion de persona: ' . $persona->fmt_nombre_completo,
                'ip' => $request->ip(),
            ]);

            DB::commit();
            flash('Persona actualizada exitosamente.')->success();
            return redirect()->route('personas.show', $persona->id_persona);

        } catch (\Exception $e) {
            DB::rollBack();
            flash('Error al actualizar la persona: ' . $e->getMessage())->error();
            return back()->withInput();
        }
    }

    /**
     * Eliminar persona
     */
    public function destroy($id)
    {
        $persona = Persona::findOrFail($id);
        $user = Auth::user();

        // Solo admin puede eliminar
        if ($user->id_nivel > 1) {
            flash('No tiene permisos para eliminar personas.')->error();
            return redirect()->route('personas.index');
        }

        // Verificar que no este vinculada a entrevistas
        $vinculada = DB::table('fichas.persona_entrevistada')
            ->where('id_persona', $id)
            ->exists();

        if ($vinculada) {
            flash('No se puede eliminar: la persona esta vinculada a entrevistas.')->error();
            return redirect()->route('personas.index');
        }

        $nombre = $persona->fmt_nombre_completo;

        // Eliminar relaciones
        DB::table('fichas.persona_poblacion')->where('id_persona', $id)->delete();
        DB::table('fichas.persona_ocupacion')->where('id_persona', $id)->delete();

        $persona->delete();

        TrazaActividad::create([
            'id_usuario' => $user->id,
            'accion' => 'eliminar_persona',
            'tabla' => 'persona',
            'id_registro' => $id,
            'descripcion' => 'Eliminacion de persona: ' . $nombre,
            'ip' => request()->ip(),
        ]);

        flash('Persona eliminada exitosamente.')->success();
        return redirect()->route('personas.index');
    }

    /**
     * Obtener catalogos para formularios
     */
    private function getCatalogos()
    {
        return [
            'sexos' => CatItem::where('id_cat', 1)->orderBy('orden')->pluck('descripcion', 'id_item')->prepend('-- Seleccione --', ''),
            'tipos_documento' => CatItem::where('id_cat', 2)->orderBy('orden')->pluck('descripcion', 'id_item')->prepend('-- Seleccione --', ''),
            'etnias' => CatItem::where('id_cat', 3)->orderBy('orden')->pluck('descripcion', 'id_item')->prepend('-- Seleccione --', ''),
            'poblaciones' => CatItem::where('id_cat', 9)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'ocupaciones' => CatItem::where('id_cat', 11)->orderBy('orden')->pluck('descripcion', 'id_item'),
            'identidades_genero' => CatItem::where('id_cat', 12)->orderBy('orden')->pluck('descripcion', 'id_item')->prepend('-- Seleccione --', ''),
            'orientaciones_sexuales' => CatItem::where('id_cat', 13)->orderBy('orden')->pluck('descripcion', 'id_item')->prepend('-- Seleccione --', ''),
            'rangos_etarios' => CatItem::where('id_cat', 14)->orderBy('orden')->pluck('descripcion', 'id_item')->prepend('-- Seleccione --', ''),
            'discapacidades' => CatItem::where('id_cat', 15)->orderBy('orden')->pluck('descripcion', 'id_item')->prepend('-- Seleccione --', ''),
            'departamentos' => Geo::where('nivel', 2)->orderBy('descripcion')->pluck('descripcion', 'id_geo')->prepend('-- Seleccione --', ''),
            'municipios' => Geo::where('nivel', 3)->orderBy('descripcion')->pluck('descripcion', 'id_geo')->prepend('-- Seleccione --', ''),
        ];
    }

    /**
     * Sync poblaciones
     */
    private function syncPoblaciones($persona, $poblaciones)
    {
        DB::table('fichas.persona_poblacion')->where('id_persona', $persona->id_persona)->delete();

        foreach ($poblaciones as $id_poblacion) {
            if ($id_poblacion) {
                DB::table('fichas.persona_poblacion')->insert([
                    'id_persona' => $persona->id_persona,
                    'id_poblacion' => $id_poblacion,
                ]);
            }
        }
    }

    /**
     * Sync ocupaciones
     */
    private function syncOcupaciones($persona, $ocupaciones)
    {
        DB::table('fichas.persona_ocupacion')->where('id_persona', $persona->id_persona)->delete();

        foreach ($ocupaciones as $id_ocupacion) {
            if ($id_ocupacion) {
                DB::table('fichas.persona_ocupacion')->insert([
                    'id_persona' => $persona->id_persona,
                    'id_ocupacion' => $id_ocupacion,
                ]);
            }
        }
    }
}
