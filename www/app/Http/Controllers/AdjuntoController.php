<?php

namespace App\Http\Controllers;

use App\Models\Adjunto;
use App\Models\Entrevista;
use App\Models\CatItem;
use App\Models\TrazaActividad;
use App\Services\TextExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdjuntoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar gestión de adjuntos de una entrevista
     */
    public function gestionar($id_entrevista)
    {
        $entrevista = Entrevista::with(['rel_adjuntos', 'rel_adjuntos.rel_tipo'])
            ->findOrFail($id_entrevista);

        $tipos = CatItem::where('id_cat', 19)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item');

        // Generar marca de agua para el visor
        Adjunto::limpiarMarcasAntiguas();
        $marcaAgua = Adjunto::generarMarcaAgua();

        return view('adjuntos.gestionar', compact('entrevista', 'tipos', 'marcaAgua'));
    }

    /**
     * Subir archivo adjunto
     */
    public function subir(Request $request, $id_entrevista)
    {
        $request->validate([
            'archivo' => 'required|file|max:512000', // 500MB max
            'id_tipo' => 'required|integer',
        ]);

        $entrevista = Entrevista::findOrFail($id_entrevista);
        $user = Auth::user();

        $archivo = $request->file('archivo');
        $nombre_original = $archivo->getClientOriginalName();
        $tipo_mime = $archivo->getMimeType();
        $tamano = $archivo->getSize();
        $md5 = md5_file($archivo->getRealPath());

        // Crear directorio basado en código de entrevista
        $codigo = $entrevista->entrevista_codigo ?? 'SIN-CODIGO';
        $carpeta = 'adjuntos/' . Str::slug($codigo);

        // Generar nombre único
        $extension = $archivo->getClientOriginalExtension();
        $nombre_archivo = time() . '_' . Str::random(8) . '.' . $extension;

        DB::beginTransaction();
        try {
            // Guardar archivo en storage
            $ruta = $archivo->storeAs($carpeta, $nombre_archivo, 'public');

            if (!$ruta) {
                throw new \Exception('Error al guardar el archivo');
            }

            // Crear registro en base de datos
            $adjunto = Adjunto::create([
                'id_e_ind_fvt' => $id_entrevista,
                'ubicacion' => $ruta,
                'nombre_original' => $nombre_original,
                'tipo_mime' => $tipo_mime,
                'id_tipo' => $request->id_tipo,
                'tamano' => $tamano,
                'tamano_bruto' => $tamano,
                'md5' => $md5,
                'existe_archivo' => 1,
            ]);

            // Registrar traza
            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => $user->id,
                'accion' => 'subir_adjunto',
                'objeto' => 'adjunto',
                'id_registro' => $adjunto->id_adjunto,
                'codigo' => $entrevista->entrevista_codigo,
                'referencia' => 'Subida de archivo: ' . $nombre_original,
                'ip' => $request->ip(),
            ]);

            DB::commit();

            // Intentar extraer texto del documento (asincrono, no bloquea)
            try {
                $extractor = new TextExtractorService();
                $texto = $extractor->extraerTexto($adjunto);
                if ($texto) {
                    $adjunto->texto_extraido = $texto;
                    $adjunto->texto_extraido_at = now();
                    $adjunto->save();
                }
            } catch (\Exception $e) {
                // No bloquear si falla la extraccion
                \Log::warning('Error extrayendo texto: ' . $e->getMessage());
            }

            flash('Archivo subido exitosamente.')->success();

        } catch (\Exception $e) {
            DB::rollBack();
            // Eliminar archivo si se subió
            if (isset($ruta) && Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
            }
            flash('Error al subir el archivo: ' . $e->getMessage())->error();
        }

        return redirect()->route('adjuntos.gestionar', $id_entrevista);
    }

    /**
     * Descargar archivo adjunto
     */
    public function descargar($id)
    {
        $adjunto = Adjunto::findOrFail($id);

        if (!$adjunto->existe_archivo || !Storage::disk('public')->exists($adjunto->ubicacion)) {
            flash('El archivo no existe o fue eliminado.')->error();
            return back();
        }

        $user = Auth::user();

        // Registrar traza de descarga
        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'descargar_adjunto',
            'objeto' => 'adjunto',
            'id_registro' => $adjunto->id_adjunto,
            'codigo' => $adjunto->rel_entrevista->entrevista_codigo ?? null,
            'referencia' => 'Descarga de archivo: ' . $adjunto->nombre_original,
            'ip' => request()->ip(),
        ]);

        return Storage::disk('public')->download(
            $adjunto->ubicacion,
            $adjunto->nombre_original
        );
    }

    /**
     * Ver/reproducir archivo adjunto
     */
    public function ver($id)
    {
        $adjunto = Adjunto::findOrFail($id);

        if (!$adjunto->existe_archivo || !Storage::disk('public')->exists($adjunto->ubicacion)) {
            flash('El archivo no existe o fue eliminado.')->error();
            return back();
        }

        $path = Storage::disk('public')->path($adjunto->ubicacion);

        return response()->file($path, [
            'Content-Type' => $adjunto->tipo_mime,
            'Content-Disposition' => 'inline; filename="' . $adjunto->nombre_original . '"'
        ]);
    }

    /**
     * Eliminar archivo adjunto
     */
    public function eliminar($id)
    {
        $adjunto = Adjunto::findOrFail($id);
        $id_entrevista = $adjunto->id_e_ind_fvt;
        $user = Auth::user();

        // Solo admin o entrevistador pueden eliminar
        if ($user->id_nivel > 2) {
            $entrevista = Entrevista::find($id_entrevista);
            if ($entrevista && $entrevista->rel_entrevistador->id_usuario != $user->id) {
                flash('No tiene permisos para eliminar este archivo.')->error();
                return redirect()->route('adjuntos.gestionar', $id_entrevista);
            }
        }

        $nombre = $adjunto->nombre_original;

        // Eliminar archivo físico
        if ($adjunto->ubicacion && Storage::disk('public')->exists($adjunto->ubicacion)) {
            Storage::disk('public')->delete($adjunto->ubicacion);
        }

        // Obtener código de entrevista antes de eliminar
        $codigo_entrevista = $adjunto->rel_entrevista->entrevista_codigo ?? null;

        // Eliminar registro
        $adjunto->delete();

        // Registrar traza
        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'eliminar_adjunto',
            'objeto' => 'adjunto',
            'id_registro' => $id,
            'codigo' => $codigo_entrevista,
            'referencia' => 'Eliminacion de archivo: ' . $nombre,
            'ip' => request()->ip(),
        ]);

        flash('Archivo eliminado exitosamente.')->success();
        return redirect()->route('adjuntos.gestionar', $id_entrevista);
    }

    /**
     * Lista de todos los adjuntos (para admin)
     */
    public function index(Request $request)
    {
        $query = Adjunto::with(['rel_entrevista', 'rel_tipo']);

        if ($request->filled('codigo')) {
            $query->whereHas('rel_entrevista', function($q) use ($request) {
                $q->where('entrevista_codigo', 'ILIKE', '%' . $request->codigo . '%');
            });
        }

        if ($request->filled('nombre')) {
            $query->where('nombre_original', 'ILIKE', '%' . $request->nombre . '%');
        }

        if ($request->filled('id_tipo')) {
            $query->where('id_tipo', $request->id_tipo);
        }

        $adjuntos = $query->orderBy('created_at', 'desc')->paginate(20);

        $tipos = CatItem::where('id_cat', 19)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        return view('adjuntos.index', compact('adjuntos', 'tipos'));
    }
}
