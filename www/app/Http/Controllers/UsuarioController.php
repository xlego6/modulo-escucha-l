<?php

namespace App\Http\Controllers;

use App\User;
use App\Models\Entrevistador;
use App\Models\CriterioFijo;
use App\Models\CatItem;
use App\Models\TrazaActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Listado de usuarios
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('name', 'ilike', "%{$buscar}%")
                  ->orWhere('email', 'ilike', "%{$buscar}%");
            });
        }

        $usuarios = $query->orderBy('name')->paginate(15);

        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Formulario de creacion
     */
    public function create()
    {
        $niveles = CriterioFijo::listado_items(1, '-- Seleccione --');
        $dependencias = CatItem::where('id_cat', 4)  // Catálogo Dependencia de Origen
            ->where('habilitado', 1)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Seleccione --', '');

        return view('usuarios.create', compact('niveles', 'dependencias'));
    }

    /**
     * Guardar nuevo usuario
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'id_nivel' => 'required|integer|min:1',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Crear perfil de entrevistador
        Entrevistador::create([
            'id_usuario' => $user->id,
            'id_dependencia_origen' => $request->id_dependencia_origen ?: null,
            'numero_entrevistador' => $this->siguienteNumeroEntrevistador(),
            'id_nivel' => $request->id_nivel,
            'solo_lectura' => $request->solo_lectura ? 1 : 0,
        ]);

        // Registrar traza
        TrazaActividad::create([
            'id_usuario' => Auth::id(),
            'accion' => 'crear_usuario',
            'tabla' => 'users',
            'id_registro' => $user->id,
            'descripcion' => "Creacion de usuario: {$user->name}",
            'ip' => $request->ip(),
        ]);

        flash('Usuario creado exitosamente.')->success();
        return redirect()->route('usuarios.index');
    }

    /**
     * Ver detalle del usuario
     */
    public function show($id)
    {
        $usuario = User::findOrFail($id);
        $perfil = Entrevistador::where('id_usuario', $id)->first();

        return view('usuarios.show', compact('usuario', 'perfil'));
    }

    /**
     * Formulario de edicion
     */
    public function edit($id)
    {
        $usuario = User::findOrFail($id);
        $perfil = Entrevistador::where('id_usuario', $id)->first();

        $niveles = CriterioFijo::listado_items(1, '-- Seleccione --');
        $dependencias = CatItem::where('id_cat', 4)  // Catálogo Dependencia de Origen
            ->where('habilitado', 1)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Seleccione --', '');

        return view('usuarios.edit', compact('usuario', 'perfil', 'niveles', 'dependencias'));
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'id_nivel' => 'required|integer|min:1',
        ]);

        $usuario->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Actualizar password solo si se proporciona
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6|confirmed']);
            $usuario->update(['password' => Hash::make($request->password)]);
        }

        // Actualizar o crear perfil
        $perfil = Entrevistador::where('id_usuario', $id)->first();
        if ($perfil) {
            $perfil->update([
                'id_dependencia_origen' => $request->id_dependencia_origen ?: null,
                'id_nivel' => $request->id_nivel,
                'solo_lectura' => $request->solo_lectura ? 1 : 0,
            ]);
        } else {
            Entrevistador::create([
                'id_usuario' => $id,
                'id_dependencia_origen' => $request->id_dependencia_origen ?: null,
                'numero_entrevistador' => $this->siguienteNumeroEntrevistador(),
                'id_nivel' => $request->id_nivel,
                'solo_lectura' => $request->solo_lectura ? 1 : 0,
            ]);
        }

        // Registrar traza
        TrazaActividad::create([
            'id_usuario' => Auth::id(),
            'accion' => 'actualizar_usuario',
            'tabla' => 'users',
            'id_registro' => $id,
            'descripcion' => "Actualizacion de usuario: {$usuario->name}",
            'ip' => $request->ip(),
        ]);

        flash('Usuario actualizado exitosamente.')->success();
        return redirect()->route('usuarios.index');
    }

    /**
     * Eliminar usuario
     */
    public function destroy(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        // No permitir eliminar al propio usuario
        if ($id == Auth::id()) {
            flash('No puede eliminar su propio usuario.')->error();
            return redirect()->route('usuarios.index');
        }

        $nombre = $usuario->name;

        // Eliminar perfil de entrevistador
        Entrevistador::where('id_usuario', $id)->delete();

        // Eliminar usuario
        $usuario->delete();

        // Registrar traza
        TrazaActividad::create([
            'id_usuario' => Auth::id(),
            'accion' => 'eliminar_usuario',
            'tabla' => 'users',
            'id_registro' => $id,
            'descripcion' => "Eliminacion de usuario: {$nombre}",
            'ip' => $request->ip(),
        ]);

        flash('Usuario eliminado exitosamente.')->success();
        return redirect()->route('usuarios.index');
    }

    /**
     * Obtener siguiente numero de entrevistador
     */
    private function siguienteNumeroEntrevistador()
    {
        $ultimo = Entrevistador::max('numero_entrevistador');
        return ($ultimo ?? 0) + 1;
    }
}
