<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AsignacionesTranscripcionExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function query()
    {
        return DB::table('esclarecimiento.asignacion_transcripcion as at')
            ->join('esclarecimiento.entrevistador as e', 'e.id_entrevistador', '=', 'at.id_transcriptor')
            ->join('users as u', 'u.id', '=', 'e.id_usuario')
            ->join('esclarecimiento.e_ind_fvt as ent', 'ent.id_e_ind_fvt', '=', 'at.id_e_ind_fvt')
            ->leftJoin('esclarecimiento.adjunto as adj', 'adj.id_adjunto', '=', 'at.id_adjunto')
            ->leftJoin('users as u_asig', 'u_asig.id', '=', 'at.id_asignado_por')
            ->leftJoin('users as u_rev', 'u_rev.id', '=', 'at.id_revisor')
            ->select(
                'at.id_asignacion',
                'ent.entrevista_codigo',
                'ent.titulo',
                'u.name as transcriptor',
                'adj.nombre_original as audio_asignado',
                DB::raw("COALESCE(adj.duracion, 0) as duracion_segundos"),
                'at.estado',
                'at.fecha_asignacion',
                'at.fecha_inicio_edicion',
                'at.fecha_envio_revision',
                'at.fecha_revision',
                DB::raw("COALESCE(at.segundos_edicion_activa, 0) as segundos_edicion_activa"),
                'u_asig.name as asignado_por',
                'u_rev.name as revisado_por',
                'at.comentario_revision',
                'at.calificacion_audio',
                'at.observaciones_envio'
            )
            ->orderBy('at.fecha_asignacion', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID Asignación',
            'Código Entrevista',
            'Título',
            'Transcriptor',
            'Audio Asignado',
            'Duración (min)',
            'Estado',
            'Fecha Asignación',
            'Fecha Inicio Edición',
            'Fecha Envío Revisión',
            'Fecha Revisión',
            'Tiempo activo edición',
            'Asignado Por',
            'Revisado Por',
            'Comentario Revisión',
            'Calificación Audio (1-5)',
            'Observaciones Envío',
        ];
    }

    public function map($row): array
    {
        $estadoLabel = [
            'asignada'         => 'Asignada',
            'en_edicion'       => 'En edición',
            'enviada_revision'  => 'En revisión',
            'aprobada'         => 'Aprobada',
            'rechazada'        => 'Rechazada',
        ][$row->estado] ?? $row->estado;

        return [
            $row->id_asignacion,
            $row->entrevista_codigo,
            $row->titulo,
            $row->transcriptor,
            $row->audio_asignado ?? '(entrevista completa)',
            $row->duracion_segundos > 0 ? round($row->duracion_segundos / 60, 2) : '',
            $estadoLabel,
            $row->fecha_asignacion,
            $row->fecha_inicio_edicion,
            $row->fecha_envio_revision,
            $row->fecha_revision,
            $row->segundos_edicion_activa > 0
                ? sprintf('%02d:%02d:%02d',
                    floor($row->segundos_edicion_activa / 3600),
                    floor(($row->segundos_edicion_activa % 3600) / 60),
                    $row->segundos_edicion_activa % 60)
                : '',
            $row->asignado_por,
            $row->revisado_por,
            $row->comentario_revision,
            $row->calificacion_audio,
            $row->observaciones_envio,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EBC01A'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
