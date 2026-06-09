<?php

namespace App\Exports;

use App\Models\TrazaActividad;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrazaExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected array $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function title(): string
    {
        return 'Traza de Actividad';
    }

    public function query()
    {
        $user = Auth::user();
        $esAdmin = in_array($user->id_nivel, [1, 2]);

        $query = TrazaActividad::with(['rel_usuario'])
            ->orderBy('fecha_hora', 'desc')
            ->limit(500);

        if ($esAdmin && !empty($this->filtros['id_usuario'])) {
            $query->where('id_usuario', $this->filtros['id_usuario']);
        }
        if (!empty($this->filtros['accion'])) {
            $query->where('accion', $this->filtros['accion']);
        }
        if (!empty($this->filtros['objeto'])) {
            $query->where('objeto', $this->filtros['objeto']);
        }
        if (!empty($this->filtros['fecha_desde'])) {
            $query->whereDate('fecha_hora', '>=', $this->filtros['fecha_desde']);
        }
        if (!empty($this->filtros['fecha_hasta'])) {
            $query->whereDate('fecha_hora', '<=', $this->filtros['fecha_hasta']);
        }
        if (!empty($this->filtros['busqueda'])) {
            $b = $this->filtros['busqueda'];
            $query->where(function($q) use ($b) {
                $q->where('codigo', 'ilike', "%{$b}%")->orWhere('referencia', 'ilike', "%{$b}%");
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Fecha/Hora', 'Usuario', 'Correo', 'Acción', 'Objeto', 'Código', 'Referencia', 'IP'];
    }

    public function map($traza): array
    {
        return [
            $traza->fecha_hora ? $traza->fecha_hora->format('d/m/Y H:i:s') : '',
            $traza->rel_usuario->name ?? 'Usuario eliminado',
            $traza->rel_usuario->email ?? '',
            $traza->accion ?? '',
            $traza->objeto ?? '',
            $traza->codigo ?? '',
            $traza->referencia ?? '',
            $traza->ip ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
