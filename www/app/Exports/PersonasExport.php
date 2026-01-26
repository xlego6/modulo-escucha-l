<?php

namespace App\Exports;

use App\Models\Persona;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PersonasExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function query()
    {
        $query = Persona::with([
            'rel_sexo',
            'rel_etnia',
            'rel_tipo_documento',
            'rel_lugar_nacimiento',
            'rel_lugar_residencia'
        ]);

        if (!empty($this->filtros['id_sexo'])) {
            $query->where('id_sexo', $this->filtros['id_sexo']);
        }

        if (!empty($this->filtros['id_etnia'])) {
            $query->where('id_etnia', $this->filtros['id_etnia']);
        }

        if (!empty($this->filtros['id_lugar_residencia_depto'])) {
            $query->where('id_lugar_residencia_depto', $this->filtros['id_lugar_residencia_depto']);
        }

        return $query->orderBy('apellido')->orderBy('nombre');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombres',
            'Apellidos',
            'Alias',
            'Tipo Documento',
            'Numero Documento',
            'Fecha Nacimiento',
            'Lugar Nacimiento',
            'Sexo',
            'Grupo Etnico',
            'Lugar Residencia',
            'Telefono',
            'Correo Electronico',
            'Ocupacion',
            'Profesion',
            'Fecha Registro',
        ];
    }

    public function map($persona): array
    {
        $fecha_nac = '';
        if ($persona->fec_nac_a) {
            $d = $persona->fec_nac_d ?? '??';
            $m = $persona->fec_nac_m ?? '??';
            $fecha_nac = "{$d}/{$m}/{$persona->fec_nac_a}";
        }

        return [
            $persona->id_persona,
            $persona->nombre,
            $persona->apellido,
            $persona->alias,
            $persona->rel_tipo_documento ? $persona->rel_tipo_documento->descripcion : '',
            $persona->num_documento,
            $fecha_nac,
            $persona->rel_lugar_nacimiento ? $persona->rel_lugar_nacimiento->descripcion : '',
            $persona->rel_sexo ? $persona->rel_sexo->descripcion : '',
            $persona->rel_etnia ? $persona->rel_etnia->descripcion : '',
            $persona->rel_lugar_residencia ? $persona->rel_lugar_residencia->descripcion : '',
            $persona->telefono,
            $persona->correo_electronico,
            $persona->ocupacion_actual,
            $persona->profesion,
            $persona->created_at,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745']
            ], 'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]],
        ];
    }
}
