<?php

namespace App\Exports;

use App\User;
use App\Models\Entrevistador;
use App\Models\FirmaCompromiso;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class UsuariosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function title(): string
    {
        return 'Usuarios';
    }

    public function collection(): Collection
    {
        return User::with([
            'rel_entrevistador',
            'rel_entrevistador.rel_dependencia_origen',
        ])
        ->orderBy('name')
        ->get();
    }

    public function headings(): array
    {
        return [
            'ID Usuario',
            'Nombre Completo',
            'Correo Electronico',
            'Rol / Nivel',
            'Dependencia',
            'Fecha Registro en Sistema',
            // Compromiso de Acceso Interno
            'Compromiso Acceso - Fecha Firma',
            'Compromiso Acceso - Version',
            'Compromiso Acceso - Texto Firmado',
            // Compromiso de Reserva
            'Compromiso Reserva - Fecha Firma',
            'Compromiso Reserva - Version',
            'Compromiso Reserva - Texto Firmado',
        ];
    }

    public function map($user): array
    {
        $entrevistador = $user->rel_entrevistador;
        $dependencia   = $entrevistador ? $entrevistador->fmt_dependencia_origen : 'Sin asignar';

        $firmaAcceso  = null;
        $firmaReserva = null;

        if ($entrevistador) {
            try {
                $firmaAcceso  = FirmaCompromiso::ultimaFirma($entrevistador->id_entrevistador, 'acceso');
                $firmaReserva = FirmaCompromiso::ultimaFirma($entrevistador->id_entrevistador, 'reserva');
            } catch (\Exception $e) {
                // Tabla compromiso_firma aún no existe (migración pendiente)
                $firmaAcceso  = null;
                $firmaReserva = null;
            }
        }

        $nivelLabels = [
            1 => 'Administrador',
            2 => 'Lider',
            3 => 'Entrevistador',
            4 => 'Transcriptor',
            5 => 'Gestor de Conocimiento',
        ];

        return [
            $user->id,
            $user->name,
            $user->email,
            $nivelLabels[$user->id_nivel] ?? 'Nivel ' . $user->id_nivel,
            $dependencia,
            $user->created_at ? $user->created_at->format('d/m/Y H:i') : '',
            // Compromiso Acceso
            $firmaAcceso  ? $firmaAcceso->fecha_firma->format('d/m/Y H:i:s')  : ($entrevistador && $entrevistador->compromiso_acceso  ? $entrevistador->compromiso_acceso->format('d/m/Y H:i:s')  . ' (sin texto)' : 'Sin firma'),
            $firmaAcceso  ? $firmaAcceso->version_texto  : '',
            $firmaAcceso  ? $firmaAcceso->texto_firmado  : '',
            // Compromiso Reserva
            $firmaReserva ? $firmaReserva->fecha_firma->format('d/m/Y H:i:s') : ($entrevistador && $entrevistador->compromiso_reserva ? $entrevistador->compromiso_reserva->format('d/m/Y H:i:s') . ' (sin texto)' : 'Sin firma'),
            $firmaReserva ? $firmaReserva->version_texto : '',
            $firmaReserva ? $firmaReserva->texto_firmado : '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2c3e50'],
                ],
            ],
        ];
    }
}
