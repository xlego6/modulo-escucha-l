<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\RolModuloPermiso;
use App\Models\TrazaActividad;
use App\Models\Entrevistador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Listado de roles
     */
    public function index()
    {
        $roles = Rol::orderBy('orden')->get();
        return view('roles.index', compact('roles'));
    }

    /**
     * Formulario de creacion de rol custom
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Guardar nuevo rol custom
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        $nivel = Rol::siguienteNivel();

        $rol = Rol::create([
            'id_nivel'    => $nivel,
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion,
            'es_sistema'  => false,
            'habilitado'  => true,
            'orden'       => $nivel,
        ]);

        // Crear filas vacías para los 13 módulos
        foreach (array_keys(RolModuloPermiso::MODULOS()) as $modulo) {
            DB::statement("
                INSERT INTO esclarecimiento.rol_modulo_permiso
                    (id_nivel, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar, alcance_propias, alcance_dependencia, alcance_todas)
                VALUES (?, ?, false, false, false, false, false, false, false)
                ON CONFLICT (id_nivel, modulo) DO NOTHING
            ", [$nivel, $modulo]);
        }

        TrazaActividad::create([
            'id_usuario'  => Auth::id(),
            'accion'      => 'crear_rol',
            'objeto'      => 'rol',
            'id_registro' => $nivel,
            'referencia'  => "Creacion de rol: {$rol->nombre}",
            'ip'          => $request->ip(),
        ]);

        flash('Rol creado. Configure sus permisos a continuacion.')->success();
        return redirect()->route('roles.edit', $nivel);
    }

    /**
     * Formulario de edición de permisos
     */
    public function edit($nivel)
    {
        $rol     = Rol::findOrFail($nivel);
        $modulos = RolModuloPermiso::MODULOS();

        // Asegurar que existan las 13 filas (por si se agregaron módulos nuevos)
        foreach (array_keys($modulos) as $modulo) {
            DB::statement("
                INSERT INTO esclarecimiento.rol_modulo_permiso
                    (id_nivel, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar, alcance_propias, alcance_dependencia, alcance_todas)
                VALUES (?, ?, false, false, false, false, false, false, false)
                ON CONFLICT (id_nivel, modulo) DO NOTHING
            ", [$nivel, $modulo]);
        }

        $permisos = RolModuloPermiso::where('id_nivel', $nivel)
            ->get()
            ->keyBy('modulo');

        $submodulos = RolModuloPermiso::SUBMODULOS();

        return view('roles.edit', compact('rol', 'modulos', 'permisos', 'submodulos'));
    }

    /**
     * Actualizar permisos del rol
     */
    public function update(Request $request, $nivel)
    {
        $rol = Rol::findOrFail($nivel);

        // El rol Administrador no puede modificarse
        if ($nivel == 1) {
            flash('Los permisos del rol Administrador no pueden modificarse.')->error();
            return redirect()->route('roles.index');
        }

        $modulosData = $request->input('modulos', []);

        // Determinar qué módulos padre tienen "ver" activo
        $padresActivos = [];
        foreach (array_keys(RolModuloPermiso::SUBMODULOS()) as $padre) {
            $datosPadre = $modulosData[$padre] ?? [];
            $padresActivos[$padre] = isset($datosPadre['puede_ver']);
        }

        foreach (array_keys(RolModuloPermiso::MODULOS()) as $modulo) {
            $datos   = $modulosData[$modulo] ?? [];
            $puedeVer = isset($datos['puede_ver']);

            // Si es submódulo, forzar desactivado si el padre no tiene "ver"
            $padre = RolModuloPermiso::getPadre($modulo);
            if ($padre && !($padresActivos[$padre] ?? false)) {
                $puedeVer = false;
            }

            // Admin no puede quedar sin acceso al módulo roles
            if ($nivel == 1 && $modulo === 'roles') {
                $puedeVer = true;
            }

            DB::table('esclarecimiento.rol_modulo_permiso')
                ->updateOrInsert(
                    ['id_nivel' => $nivel, 'modulo' => $modulo],
                    [
                        'puede_ver'           => $puedeVer,
                        'puede_crear'         => $puedeVer && isset($datos['puede_crear']),
                        'puede_editar'        => $puedeVer && isset($datos['puede_editar']),
                        'puede_eliminar'      => $puedeVer && isset($datos['puede_eliminar']),
                        'alcance_propias'     => $puedeVer && isset($datos['alcance_propias']),
                        'alcance_dependencia' => $puedeVer && isset($datos['alcance_dependencia']),
                        'alcance_todas'       => $puedeVer && isset($datos['alcance_todas']),
                    ]
                );
        }

        RolModuloPermiso::clearCache($nivel);

        TrazaActividad::create([
            'id_usuario'  => Auth::id(),
            'accion'      => 'actualizar_permisos_rol',
            'objeto'      => 'rol',
            'id_registro' => $nivel,
            'referencia'  => "Actualizacion de permisos del rol: {$rol->nombre}",
            'ip'          => $request->ip(),
        ]);

        flash('Permisos actualizados correctamente.')->success();
        return redirect()->route('roles.edit', $nivel);
    }

    /**
     * Eliminar rol custom (no se eliminan roles del sistema)
     */
    public function destroy(Request $request, $nivel)
    {
        $rol = Rol::findOrFail($nivel);

        if ($rol->es_sistema) {
            flash('No se puede eliminar un rol del sistema.')->error();
            return redirect()->route('roles.index');
        }

        $enUso = Entrevistador::where('id_nivel', $nivel)->count();
        if ($enUso > 0) {
            flash("No se puede eliminar el rol: {$enUso} usuario(s) tienen este rol asignado.")->error();
            return redirect()->route('roles.index');
        }

        $nombre = $rol->nombre;

        RolModuloPermiso::where('id_nivel', $nivel)->delete();
        $rol->delete();

        RolModuloPermiso::clearCache($nivel);

        TrazaActividad::create([
            'id_usuario'  => Auth::id(),
            'accion'      => 'eliminar_rol',
            'objeto'      => 'rol',
            'id_registro' => $nivel,
            'referencia'  => "Eliminacion de rol: {$nombre}",
            'ip'          => $request->ip(),
        ]);

        flash('Rol eliminado correctamente.')->success();
        return redirect()->route('roles.index');
    }
}
