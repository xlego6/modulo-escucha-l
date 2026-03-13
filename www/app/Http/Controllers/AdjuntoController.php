<?php

namespace App\Http\Controllers;

use App\Models\Adjunto;
use App\Models\Entrevista;
use App\Models\CatItem;
use App\Models\TrazaActividad;
use App\Models\RolModuloPermiso;
use App\Services\TextExtractorService;
use App\Services\TranscripcionDocService;
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
        $entrevista = Entrevista::with(['rel_adjuntos', 'rel_adjuntos.rel_tipo', 'rel_entrevistador'])
            ->findOrFail($id_entrevista);

        $tipos = CatItem::where('id_cat', 19)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item');

        // Generar marca de agua para el visor
        Adjunto::limpiarMarcasAntiguas();
        $marcaAgua = Adjunto::generarMarcaAgua();

        $user = Auth::user();
        $entrevistadorActual = \App\Models\Entrevistador::where('id_usuario', $user->id)->first();

        // Determine permissions
        $esPropietario = $entrevista->rel_entrevistador &&
            $entrevista->rel_entrevistador->id_usuario == $user->id;

        // Can delete/upload/download: rol con edición total o propietario
        $puedeGestionar = (RolModuloPermiso::puedeEditar($user->id_nivel, 'adjuntos') &&
                           RolModuloPermiso::alcanceTodas($user->id_nivel, 'adjuntos')) || $esPropietario;

        // Alcance por dependencia: puede ver pero no gestionar
        $esGestorMismaDependencia = RolModuloPermiso::puedeVer($user->id_nivel, 'adjuntos') &&
            RolModuloPermiso::alcanceDependencia($user->id_nivel, 'adjuntos') &&
            $entrevistadorActual && $entrevista->id_dependencia_origen &&
            $entrevistadorActual->id_dependencia_origen == $entrevista->id_dependencia_origen;

        // Has approved access permission
        $tienePermisoAcceso = false;
        if ($entrevistadorActual) {
            $tienePermisoAcceso = (bool) DB::table('esclarecimiento.permiso')
                ->where('id_entrevistador', (int) $entrevistadorActual->id_entrevistador)
                ->where('id_e_ind_fvt', (int) $id_entrevista)
                ->where('id_estado', \App\Models\Permiso::ESTADO_VIGENTE)
                ->where(function($q) {
                    $q->where('es_solicitud', false)
                      ->orWhere(function($q2) {
                          $q2->where('es_solicitud', true)
                             ->where('estado_solicitud', \App\Models\Permiso::SOLICITUD_APROBADA);
                      });
                })
                ->where(function($q) {
                    $q->whereNull('fecha_vencimiento')
                      ->orWhere('fecha_vencimiento', '>', now());
                })
                ->exists();
        }

        // Can view/play: alcance total, propietario, dependencia, o permiso otorgado
        $puedeVer = (RolModuloPermiso::puedeVer($user->id_nivel, 'adjuntos') &&
                     RolModuloPermiso::alcanceTodas($user->id_nivel, 'adjuntos')) ||
                    $esPropietario || $esGestorMismaDependencia || $tienePermisoAcceso;

        return view('adjuntos.gestionar', compact('entrevista', 'tipos', 'marcaAgua', 'puedeGestionar', 'puedeVer', 'esGestorMismaDependencia', 'tienePermisoAcceso'));
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

            // Extraer duración para audio/video
            if (str_contains($tipo_mime, 'audio') || str_contains($tipo_mime, 'video')) {
                $rutaAbsoluta = Storage::disk('public')->path($ruta);
                $duracion = self::extraerDuracion($rutaAbsoluta);
                if ($duracion) {
                    $adjunto->duracion = $duracion;
                    $adjunto->save();
                }
            }

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
     * Descargar archivo adjunto (PDFs se entregan con marca de agua)
     */
    public function descargar($id)
    {
        $adjunto = Adjunto::findOrFail($id);

        if (!$adjunto->existe_archivo || !Storage::disk('public')->exists($adjunto->ubicacion)) {
            flash('El archivo no existe o fue eliminado.')->error();
            return back();
        }

        $user = Auth::user();

        TrazaActividad::create([
            'fecha_hora'  => now(),
            'id_usuario'  => $user->id,
            'accion'      => 'descargar_adjunto',
            'objeto'      => 'adjunto',
            'id_registro' => $adjunto->id_adjunto,
            'codigo'      => $adjunto->rel_entrevista->entrevista_codigo ?? null,
            'referencia'  => 'Descarga de archivo: ' . $adjunto->nombre_original,
            'ip'          => request()->ip(),
        ]);

        // Aplicar marca de agua en PDFs antes de entregar
        if (str_contains($adjunto->tipo_mime ?? '', 'pdf')) {
            $textoMarca = $user->name . ' - ' . now()->format('d/m/Y H:i:s');
            $pdfMarcado = Adjunto::aplicarMarcaAguaDescarga($adjunto->ubicacion, $textoMarca);

            if ($pdfMarcado && file_exists($pdfMarcado)) {
                return response()->download($pdfMarcado, $adjunto->nombre_original, [
                    'Content-Type' => 'application/pdf',
                ])->deleteFileAfterSend(true);
            }
            // Si falla el marcado, entregar sin marca (no bloquear al usuario)
        }

        return Storage::disk('public')->download(
            $adjunto->ubicacion,
            $adjunto->nombre_original
        );
    }

    /**
     * Ver/reproducir archivo adjunto (audio, video, imágenes)
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
     * Servir PDF de forma segura para el visor PDF.js (con control de acceso y traza)
     */
    public function verPdf($id)
    {
        $adjunto = Adjunto::with('rel_entrevista.rel_entrevistador')->findOrFail($id);

        if (!$adjunto->existe_archivo || !Storage::disk('public')->exists($adjunto->ubicacion)) {
            abort(404);
        }

        if (!str_contains($adjunto->tipo_mime ?? '', 'pdf')) {
            abort(403);
        }

        $user = Auth::user();
        $entrevista = $adjunto->rel_entrevista;

        // Verificar que el usuario puede ver el adjunto
        if (!$this->puedeVerAdjunto($adjunto, $user)) {
            abort(403);
        }

        // Registrar consulta en traza
        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion'     => 'ver_pdf',
            'objeto'     => 'adjunto',
            'id_registro'=> $adjunto->id_adjunto,
            'codigo'     => $entrevista->entrevista_codigo ?? null,
            'referencia' => 'Consulta PDF en visor: ' . $adjunto->nombre_original,
            'ip'         => request()->ip(),
        ]);

        $path = Storage::disk('public')->path($adjunto->ubicacion);

        return response()->file($path, [
            'Content-Type'              => 'application/pdf',
            'Content-Disposition'       => 'inline; filename="archivo.pdf"',
            'X-Content-Type-Options'    => 'nosniff',
            'Cache-Control'             => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Verificar si el usuario autenticado puede ver/reproducir un adjunto.
     */
    private function puedeVerAdjunto(Adjunto $adjunto, $user): bool
    {
        $entrevista = $adjunto->rel_entrevista;
        if (!$entrevista) {
            return false;
        }

        // Alcance total
        if (RolModuloPermiso::puedeVer($user->id_nivel, 'adjuntos') &&
            RolModuloPermiso::alcanceTodas($user->id_nivel, 'adjuntos')) {
            return true;
        }

        // Propietario
        if ($entrevista->rel_entrevistador && $entrevista->rel_entrevistador->id_usuario == $user->id) {
            return true;
        }

        $entrevistador = \App\Models\Entrevistador::where('id_usuario', $user->id)->first();

        // Misma dependencia
        if ($entrevistador &&
            RolModuloPermiso::puedeVer($user->id_nivel, 'adjuntos') &&
            RolModuloPermiso::alcanceDependencia($user->id_nivel, 'adjuntos') &&
            $entrevista->id_dependencia_origen &&
            $entrevistador->id_dependencia_origen == $entrevista->id_dependencia_origen) {
            return true;
        }

        // Permiso de acceso otorgado
        if ($entrevistador) {
            return (bool) DB::table('esclarecimiento.permiso')
                ->where('id_entrevistador', (int) $entrevistador->id_entrevistador)
                ->where('id_e_ind_fvt', (int) $adjunto->id_e_ind_fvt)
                ->where('id_estado', \App\Models\Permiso::ESTADO_VIGENTE)
                ->where(function ($q) {
                    $q->where('es_solicitud', false)
                      ->orWhere(function ($q2) {
                          $q2->where('es_solicitud', true)
                             ->where('estado_solicitud', \App\Models\Permiso::SOLICITUD_APROBADA);
                      });
                })
                ->where(function ($q) {
                    $q->whereNull('fecha_vencimiento')
                      ->orWhere('fecha_vencimiento', '>', now());
                })
                ->exists();
        }

        return false;
    }

    /**
     * Eliminar archivo adjunto
     */
    public function eliminar($id)
    {
        $adjunto = Adjunto::findOrFail($id);
        $id_entrevista = $adjunto->id_e_ind_fvt;
        $user = Auth::user();

        // Puede eliminar si tiene alcance total, o si es propietario de la entrevista
        $puedeEliminarTodo = RolModuloPermiso::puedeEliminar($user->id_nivel, 'adjuntos') &&
                             RolModuloPermiso::alcanceTodas($user->id_nivel, 'adjuntos');
        if (!$puedeEliminarTodo) {
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
     * Descargar transcripción en formato FormTR (DOCX o PDF)
     *
     * @param int    $id_entrevista
     * @param string $tipo    'auto' | 'final'
     * @param string $formato 'docx' | 'pdf'
     */
    public function descargarFormTR($id_entrevista, string $tipo, string $formato)
    {
        $entrevista = Entrevista::with([
            'rel_adjuntos',
            'rel_entrevistador.rel_usuario',
            'rel_lugar_entrevista',
            'rel_dependencia_origen',
            'rel_tipo_testimonio',
            'rel_modalidades',
        ])->findOrFail($id_entrevista);

        // Verificar que existe transcripción del tipo solicitado
        $tipoAdjunto = $tipo === 'final'
            ? Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_FINAL
            : Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA;

        $adjunto = $entrevista->rel_adjuntos->firstWhere('id_tipo', $tipoAdjunto);

        if (!$adjunto || empty($adjunto->texto_extraido)) {
            flash('No hay transcripción disponible para generar el documento.')->error();
            return back();
        }

        $service  = new TranscripcionDocService();
        $tipoDoc  = $tipo === 'final' ? 'final' : 'auto';
        $codigo   = $entrevista->entrevista_codigo;
        $sufijo   = $tipo === 'final' ? 'Final' : 'Auto';

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => Auth::id(),
            'accion'     => 'descargar_formtr',
            'objeto'     => 'entrevista',
            'id_registro'=> $entrevista->id_e_ind_fvt,
            'codigo'     => $codigo,
            'referencia' => "Descarga FormTR ({$sufijo}) en " . strtoupper($formato),
            'ip'         => request()->ip(),
        ]);

        if ($formato === 'pdf') {
            $pdfPath  = $service->generarPdf($entrevista, $tipoDoc);
            $filename = "FormTR_{$codigo}_{$sufijo}.pdf";
            return response()->download($pdfPath, $filename, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        }

        // DOCX
        $tmpPath  = $service->generarDocx($entrevista, $tipoDoc);
        $filename = "FormTR_{$codigo}_{$sufijo}.docx";

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
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

    /**
     * Iniciar conversión FLV → MP4 en background (ffmpeg).
     * Retorna estado inmediatamente; el cliente hace polling.
     */
    public function iniciarConversionFlv($id)
    {
        $adjunto = Adjunto::findOrFail($id);
        if (!$this->puedeVerAdjunto($adjunto, Auth::user())) {
            return response()->json(['error' => 'Sin permiso'], 403);
        }

        $mp4Path    = storage_path('app/flv_cache/' . $id . '.mp4');
        $lockPath   = $mp4Path . '.lock';

        if (file_exists($mp4Path)) {
            return response()->json(['status' => 'ready', 'url' => route('adjuntos.flv_play', $id)]);
        }
        if (file_exists($lockPath)) {
            return response()->json(['status' => 'converting']);
        }

        $rutaOriginal = Storage::disk('public')->path($adjunto->ubicacion);
        $dir = dirname($mp4Path);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        touch($lockPath);

        $ffmpeg  = trim(shell_exec('which ffmpeg 2>/dev/null') ?? '') ?: 'ffmpeg';
        $logFile = $mp4Path . '.log';

        // Conversión en background: al terminar exitosamente elimina el lock
        $cmd = sprintf(
            'nohup %s -i %s -c:v libx264 -preset fast -crf 23 -c:a aac -movflags +faststart %s -y > %s 2>&1 && rm -f %s &',
            escapeshellcmd($ffmpeg),
            escapeshellarg($rutaOriginal),
            escapeshellarg($mp4Path),
            escapeshellarg($logFile),
            escapeshellarg($lockPath)
        );
        exec($cmd);

        return response()->json(['status' => 'converting']);
    }

    /**
     * Consultar estado de la conversión FLV → MP4.
     */
    public function estadoConversionFlv($id)
    {
        $mp4Path  = storage_path('app/flv_cache/' . $id . '.mp4');
        $lockPath = $mp4Path . '.lock';

        if (file_exists($mp4Path) && !file_exists($lockPath)) {
            return response()->json(['status' => 'ready', 'url' => route('adjuntos.flv_play', $id)]);
        }
        if (file_exists($lockPath)) {
            return response()->json(['status' => 'converting']);
        }
        return response()->json(['status' => 'pending']);
    }

    /**
     * Servir el MP4 convertido desde el cache FLV.
     */
    public function reproducirFlv($id)
    {
        $adjunto = Adjunto::findOrFail($id);
        if (!$this->puedeVerAdjunto($adjunto, Auth::user())) {
            abort(403);
        }

        $mp4Path = storage_path('app/flv_cache/' . $id . '.mp4');
        if (!file_exists($mp4Path)) {
            abort(404, 'Video no convertido aún.');
        }

        return response()->file($mp4Path, [
            'Content-Type'        => 'video/mp4',
            'Content-Disposition' => 'inline; filename="video.mp4"',
        ]);
    }

    /**
     * Extraer duración en segundos de un archivo de audio/video usando ffprobe.
     * Devuelve null si ffprobe no está disponible o el archivo no tiene duración.
     */
    public static function extraerDuracion(string $rutaAbsoluta): ?int
    {
        if (!file_exists($rutaAbsoluta)) {
            return null;
        }

        $ffprobe = trim(shell_exec('which ffprobe 2>/dev/null') ?? '');
        if (!$ffprobe) {
            $ffprobe = 'ffprobe';
        }

        $cmd = sprintf(
            '%s -v quiet -print_format json -show_format %s 2>/dev/null',
            escapeshellcmd($ffprobe),
            escapeshellarg($rutaAbsoluta)
        );

        $output = shell_exec($cmd);
        if (!$output) {
            return null;
        }

        $data = json_decode($output, true);
        $duracion = $data['format']['duration'] ?? null;

        if ($duracion === null || $duracion <= 0) {
            return null;
        }

        return (int)round((float)$duracion);
    }
}
