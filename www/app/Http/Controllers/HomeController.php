<?php

namespace App\Http\Controllers;

use App\Models\Entrevista;
use App\Models\Entrevistador;
use App\Models\FirmaCompromiso;
use App\Models\Persona;
use App\Models\Adjunto;
use App\Models\TrazaActividad;
use App\Helpers\CompromisoTextos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $stats = [
            'total_entrevistas' => Entrevista::where('id_activo', 1)->count(),
            'total_personas' => Persona::count(),
            'total_adjuntos' => Adjunto::count(),
            'entrevistas_mes' => Entrevista::where('id_activo', 1)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];

        $ultimas_entrevistas = Entrevista::where('id_activo', 1)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('home.index', compact('stats', 'ultimas_entrevistas'));
    }

    public function perfil()
    {
        $user = Auth::user();
        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();
        return view('home.perfil', compact('user', 'entrevistador'));
    }

    /**
     * Actualizar datos del perfil
     */
    public function actualizarPerfil(Request $request)
    {
        $user = Auth::user();

        if ($user->id_nivel != 1) {
            flash('No tiene permisos para editar nombre o correo electronico.')->error();
            return redirect()->route('perfil');
        }

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->name  = $request->name;
        $user->email = $request->email;

        $user->save();

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'editar',
            'objeto' => 'perfil',
            'id_registro' => $user->id,
            'referencia' => 'Actualizacion de datos del perfil',
            'ip' => $request->ip(),
        ]);

        flash('Perfil actualizado correctamente.')->success();
        return redirect()->route('perfil');
    }

    /**
     * Cambiar contraseña (solo Admin — perfiles de directorio activo gestionan
     * su contraseña desde fuera del sistema)
     */
    public function cambiarPassword(Request $request)
    {
        $user = Auth::user();

        if ($user->id_nivel != 1) {
            flash('El cambio de contraseña no está disponible para su perfil. Gestione su contraseña desde el directorio activo.')->warning();
            return redirect()->route('perfil');
        }

        $request->validate([
            'password_actual' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password_actual.required' => 'Debe ingresar su contraseña actual',
            'password.required' => 'Debe ingresar la nueva contraseña',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        // Verificar contraseña actual
        if (!Hash::check($request->password_actual, $user->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual es incorrecta']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'cambiar_password',
            'objeto' => 'usuario',
            'id_registro' => $user->id,
            'referencia' => 'Cambio de contraseña',
            'ip' => $request->ip(),
        ]);

        flash('Contraseña actualizada correctamente.')->success();
        return redirect()->route('perfil');
    }

    /**
     * Descargar certificado PDF de un compromiso aceptado
     */
    public function descargarCertificado(string $tipo)
    {
        $user = Auth::user();
        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if (!$entrevistador) {
            abort(403, 'Perfil de entrevistador no encontrado.');
        }

        $tiposPermitidos = ['acceso', 'reserva'];
        if (!in_array($tipo, $tiposPermitidos)) {
            abort(404);
        }

        // Verificar que el compromiso está aceptado
        $aceptado = ($tipo === 'acceso')
            ? $entrevistador->compromiso_acceso
            : $entrevistador->compromiso_reserva;

        if (!$aceptado) {
            return redirect()->route('perfil')
                ->with('error', 'Debe aceptar el compromiso antes de descargar el certificado.');
        }

        $firma = FirmaCompromiso::ultimaFirma($entrevistador->id_entrevistador, $tipo);

        if (!$firma) {
            return redirect()->route('perfil')
                ->with('error', 'No se encontró el registro del compromiso firmado.');
        }

        $docxPath = $this->generarCertificadoDocx($user, $firma, $tipo);
        $pdfPath  = $this->convertirDocxAPdf($docxPath);

        $nombreTipo = $tipo === 'acceso' ? 'acceso-interno' : 'confidencialidad-reserva';
        $nombreArchivo = "certificado-{$nombreTipo}-{$user->id}.pdf";

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion'     => 'descargar',
            'objeto'     => 'certificado_compromiso',
            'id_registro' => $entrevistador->id_entrevistador,
            'referencia' => "Descarga certificado {$tipo} (version: {$firma->version_texto})",
            'ip'         => request()->ip(),
        ]);

        return response()->download($pdfPath, $nombreArchivo)->deleteFileAfterSend(true);
    }

    private function generarCertificadoDocx($user, FirmaCompromiso $firma, string $tipo): string
    {
        $labelTipo = $tipo === 'acceso'
            ? 'Compromiso de Acceso Interno'
            : 'Compromiso de Confidencialidad, Reserva y No Divulgación';

        $fechaFirma = $firma->fecha_firma->format('d') . ' de ' .
            collect(['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'])
                ->get((int)$firma->fecha_firma->format('m') - 1) .
            ' de ' . $firma->fecha_firma->format('Y');

        $templatePath = storage_path('app/templates/FormCER.docx');
        $processor    = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        $processor->setValue('NOMBRE_USUARIO',  htmlspecialchars($user->name));
        $processor->setValue('TIPO_COMPROMISO', htmlspecialchars($labelTipo));
        $processor->setValue('VERSION',         htmlspecialchars($firma->version_texto));
        $processor->setValue('FECHA_FIRMA',     htmlspecialchars($fechaFirma));
        $processor->setValue('TEXTO_COMPROMISO', $this->textoCompromisoAXml($firma->texto_firmado));

        $tmpPath = tempnam(sys_get_temp_dir(), 'FormCER_') . '.docx';
        $processor->saveAs($tmpPath);

        return $tmpPath;
    }

    private function convertirDocxAPdf(string $docxPath): string
    {
        $tmpDir = dirname($docxPath);
        $cmd = sprintf(
            'HOME=/tmp soffice --headless --norestore --convert-to pdf --outdir %s %s 2>/dev/null',
            escapeshellarg($tmpDir),
            escapeshellarg($docxPath)
        );
        shell_exec($cmd);

        $pdfPath = substr($docxPath, 0, -5) . '.pdf';
        @unlink($docxPath);

        if (!file_exists($pdfPath)) {
            throw new \Exception('Error al convertir el certificado a PDF con LibreOffice.');
        }

        return $pdfPath;
    }

    private function textoCompromisoAXml(string $texto): string
    {
        $lineas = explode("\n", str_replace(["\r\n", "\r"], "\n", $texto));

        $xml = '</w:t></w:r></w:p>';

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                $xml .= '<w:p/>';
                continue;
            }
            // Palabras en MAYÚSCULAS → negrita
            $innerXml = preg_replace_callback('/\b([A-ZÁÉÍÓÚÑÜ]{2,})\b/u', function($m) {
                return '</w:t></w:r><w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">'
                    . htmlspecialchars($m[1], ENT_XML1)
                    . '</w:t></w:r><w:r><w:t xml:space="preserve">';
            }, htmlspecialchars($linea, ENT_XML1));

            $xml .= '<w:p><w:pPr><w:jc w:val="both"/></w:pPr>'
                . '<w:r><w:t xml:space="preserve">' . $innerXml . '</w:t></w:r></w:p>';
        }

        $xml .= '<w:p><w:r><w:t>';

        return $xml;
    }

    /**
     * Aceptar compromiso de acceso interno
     */
    public function aceptarCompromisoAcceso(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'acepto_compromiso_acceso' => 'required|accepted',
        ], [
            'acepto_compromiso_acceso.accepted' => 'Debe aceptar el compromiso de acceso',
        ]);

        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if ($entrevistador) {
            $ahora = now();
            $entrevistador->compromiso_acceso = $ahora;
            $entrevistador->save();

            // Guardar texto firmado para auditoría
            FirmaCompromiso::create([
                'id_entrevistador' => $entrevistador->id_entrevistador,
                'tipo'             => 'acceso',
                'version_texto'    => CompromisoTextos::ACCESO_VERSION,
                'fecha_firma'      => $ahora,
                'texto_firmado'    => CompromisoTextos::textoAcceso(
                    $user->name,
                    $entrevistador->fmt_dependencia_origen
                ),
            ]);

            TrazaActividad::create([
                'fecha_hora' => $ahora,
                'id_usuario' => $user->id,
                'accion' => 'aceptar_compromiso',
                'objeto' => 'compromiso_acceso',
                'id_registro' => $entrevistador->id_entrevistador,
                'referencia' => 'Aceptacion del compromiso de acceso interno (version: ' . CompromisoTextos::ACCESO_VERSION . ')',
                'ip' => $request->ip(),
            ]);

            flash('Compromiso de acceso aceptado correctamente.')->success();
        } else {
            flash('No se encontró el perfil de entrevistador.')->error();
        }

        return redirect()->route('perfil');
    }

    /**
     * Aceptar compromiso de reserva
     */
    public function aceptarCompromisoReserva(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'acepto_compromiso' => 'required|accepted',
        ], [
            'acepto_compromiso.accepted' => 'Debe aceptar el compromiso de reserva',
        ]);

        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if ($entrevistador) {
            $ahora = now();
            $rolLabel = $user->id_nivel == 2 ? 'lider(a)' : 'transcriptor(a)';
            $entrevistador->compromiso_reserva = $ahora;
            $entrevistador->save();

            // Guardar texto firmado para auditoría
            FirmaCompromiso::create([
                'id_entrevistador' => $entrevistador->id_entrevistador,
                'tipo'             => 'reserva',
                'version_texto'    => CompromisoTextos::RESERVA_VERSION,
                'fecha_firma'      => $ahora,
                'texto_firmado'    => CompromisoTextos::textoReserva($user->name, $rolLabel),
            ]);

            TrazaActividad::create([
                'fecha_hora' => $ahora,
                'id_usuario' => $user->id,
                'accion' => 'aceptar_compromiso',
                'objeto' => 'compromiso_reserva',
                'id_registro' => $entrevistador->id_entrevistador,
                'referencia' => 'Aceptacion del compromiso de reserva (version: ' . CompromisoTextos::RESERVA_VERSION . ')',
                'ip' => $request->ip(),
            ]);

            flash('Compromiso de reserva aceptado correctamente.')->success();
        } else {
            flash('No se encontró el perfil de entrevistador.')->error();
        }

        return redirect()->route('perfil');
    }
}
