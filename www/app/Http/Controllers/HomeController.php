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
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

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

        $docxPath = $this->generarCertificadoDocx($user, $entrevistador, $firma, $tipo);
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

    private function generarCertificadoDocx($user, $entrevistador, FirmaCompromiso $firma, string $tipo): string
    {
        $phpWord = new PhpWord();

        $phpWord->setDefaultFontName('Barlow');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop'    => 1440,
            'marginBottom' => 1440,
            'marginLeft'   => 1800,
            'marginRight'  => 1800,
        ]);

        $styleTitle = ['bold' => true, 'size' => 13, 'name' => 'Barlow'];
        $styleSubtitle = ['bold' => true, 'size' => 11, 'name' => 'Barlow'];
        $styleNormal = ['size' => 10, 'name' => 'Barlow'];
        $styleMuted = ['size' => 9, 'name' => 'Barlow', 'color' => '666666'];
        $parCenter = ['alignment' => 'center'];
        $parBoth   = ['alignment' => 'both', 'spaceAfter' => 120];

        // Encabezado institucional
        $section->addText('Centro Nacional de Memoria Histórica', $styleTitle, $parCenter);
        $section->addText('Dirección de Archivos de los Derechos Humanos', $styleSubtitle, $parCenter);
        $section->addTextBreak(1);

        // Título del certificado
        $labelTipo = $tipo === 'acceso'
            ? 'COMPROMISO DE ACCESO INTERNO'
            : 'COMPROMISO DE CONFIDENCIALIDAD, RESERVA Y NO DIVULGACIÓN';

        $section->addText('CERTIFICADO DE', ['bold' => true, 'size' => 14, 'name' => 'Barlow'], $parCenter);
        $section->addText($labelTipo, ['bold' => true, 'size' => 14, 'name' => 'Barlow'], $parCenter);
        $section->addTextBreak(1);

        // Datos del firmante
        $fechaFirma = $firma->fecha_firma->format('d') . ' de ' .
            collect(['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'])
                ->get((int)$firma->fecha_firma->format('m') - 1) .
            ' de ' . $firma->fecha_firma->format('Y');

        $section->addText('Datos de la firma', $styleSubtitle, ['spaceAfter' => 60]);
        $section->addText("Nombre: {$user->name}", $styleNormal, ['spaceAfter' => 40]);
        $section->addText("Versión del compromiso: {$firma->version_texto}", $styleNormal, ['spaceAfter' => 40]);
        $section->addText("Fecha de aceptación: {$fechaFirma}", $styleNormal, ['spaceAfter' => 40]);
        $section->addText("Sistema: Módulo de Escucha — CNMH", $styleNormal, ['spaceAfter' => 0]);
        $section->addTextBreak(1);

        // Separador visual (párrafo con borde inferior)
        $phpWord->addParagraphStyle('separador', ['borderBottomSize' => 6, 'borderBottomColor' => 'CCCCCC', 'spaceAfter' => 120]);
        $section->addText('', null, 'separador');
        $section->addTextBreak(1);

        // Texto del compromiso
        $section->addText('Texto del compromiso aceptado', $styleSubtitle, ['spaceAfter' => 80]);

        $lineas = explode("\n", $firma->texto_firmado);
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                $section->addTextBreak(1);
            } else {
                $section->addText($linea, $styleNormal, $parBoth);
            }
        }

        $section->addTextBreak(2);
        $section->addText(
            'Este certificado fue generado automáticamente por el Módulo de Escucha del CNMH el ' .
            now()->format('d/m/Y H:i') . '.',
            $styleMuted,
            $parCenter
        );

        $tmpPath = tempnam(sys_get_temp_dir(), 'CER_') . '.docx';
        $writer  = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

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
            throw new \Exception('Error al convertir el certificado a PDF. Asegúrese de que LibreOffice esté instalado.');
        }

        return $pdfPath;
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
