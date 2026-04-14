<?php

namespace App\Services;

use App\Models\CatItem;
use App\Models\Geo;

/**
 * Servicio para parsear y validar el CSV de importación masiva.
 *
 * Estructura de columnas esperada (0-indexed, separador ";"):
 *   0  ID agrupar
 *   2  Ruta de resguardo (audio/video principal)
 *   8  Título
 *   9  Dependencia de Origen
 *  10  Equipo/Estrategia
 *  11  Nombre proyecto/investigación
 *  12  Tipo de testimonio
 *  13  Formato del testimonio
 *  14  Número de personas que brindan testimonio
 *  15  Lugar geográfico – Departamento (toma)
 *  16  Lugar geográfico – Municipio (toma)
 *  17  Modalidad
 *  18  Fecha toma inicial
 *  19  Fecha toma final
 *  20  Idioma del testimonio
 *  21  Necesidades de ruta de reparación
 *  22  Áreas compatibles
 *  23  Observaciones toma
 *  24  Anexo al testimonio (Sí/No)
 *  25  Descripción de anexo
 *  26  Nombre y apellido del testimoniante
 *  27  Lugar geográfico origen – Departamento
 *  28  Lugar geográfico origen – Municipio
 *  29  Nombre identitario
 *  30  Población
 *  31  Ocupación
 *  32  Sexo
 *  33  Identidad de género
 *  34  Orientación sexual
 *  35  Grupos étnicos
 *  36  Etario
 *  37  Edad
 *  38  Discapacidad
 *  39  Consentimiento – ¿Tiene documento?
 *  40  Consentimiento – ¿Es menor de edad?
 *  41  Consentimiento – Autoriza ser entrevistado
 *  42  Consentimiento – Permite grabación
 *  43  Consentimiento – Permite procesamiento misional
 *  44  Consentimiento – Permite uso conservación consulta
 *  45  Consentimiento – Considera riesgo de seguridad
 *  46  Consentimiento – Autoriza datos personales sin anonimizar
 *  47  Consentimiento – Autoriza datos sensibles sin anonimizar
 *  48  Consentimiento – Observaciones
 *  49  Contenido: Poblaciones mencionadas
 *  50  Contenido: Ocupaciones mencionadas
 *  51  Contenido: Sexos mencionados
 *  52  Contenido: Identidades de género
 *  53  Contenido: Orientaciones sexuales
 *  54  Contenido: Grupos étnicos
 *  55  Contenido: Rangos etarios
 *  56  Contenido: Discapacidades
 *  57  Contenido: Hechos victimizantes
 *  58  Contenido: Responsables colectivos
 *  59  Contenido: Responsables individuales
 *  60  Hechos – Fecha inicial
 *  61  Hechos – Fecha final
 *  62  Contenido: Lugar geográfico – Departamento
 *  63  Contenido: Lugar geográfico – Municipio
 *  64  Temas abordados
 *  65  Prueba de daño – Derechos privados (Sí / No / No sabe)
 *  66  Prueba de daño – Intereses públicos (Sí / No / No sabe)
 *  67  Prueba de daño – Inteligencia (Sí / No / No sabe)
 *  68  Prueba de daño – NNA (Sí / No / No sabe)
 *  69  Contenido: Otras poblaciones mencionadas (texto libre)
 *  70  Contenido: Otras ocupaciones mencionadas (texto libre)
 *  71  Contenido: Detalle grupos étnicos (texto libre)
 *  72  Contenido: Otros hechos victimizantes (texto libre)
 *  73  Contenido: Prácticas de resistencia (valores catálogo separados por coma)
 *  74  Contenido: Detalle resistencias (texto libre)
 *  75  (reservado)
 *  76  Ruta archivo Consentimiento
 *  77  Ruta archivo Transcripción
 *  78  Ruta otros archivos
 */
class ImportacionMasivaService
{
    // Extensiones reconocidas por tipo
    const EXT_AUDIO_VIDEO = ['mp4', 'mkv', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'opus', 'wma', 'm4v'];
    const EXT_DOCUMENTO   = ['pdf', 'doc', 'docx', 'odt', 'txt', 'rtf'];

    // Catálogos que requieren mapeo por nombre
    const CATALOGOS = [
        'dependencia'               => 4,
        'equipo_estrategia'         => 18,
        'tipo_testimonio'           => 5,
        'formato'                   => 6,
        'modalidad'                 => 7,
        'idioma'                    => 8,
        'necesidad_reparacion'      => 16,
        'areas_compatibles'         => 4,   // mismo catálogo que dependencia
        'poblacion'                 => 9,
        'ocupacion'                 => 11,
        'sexo'                      => 1,
        'identidad_genero'          => 12,
        'orientacion_sexual'        => 13,
        'etnia'                     => 3,
        'rango_etario'              => 14,
        'discapacidad'              => 15,
        'contenido_poblacion'       => 9,
        'contenido_ocupacion'       => 11,
        'contenido_sexo'            => 1,
        'contenido_identidad'       => 12,
        'contenido_orientacion'     => 13,
        'contenido_etnia'           => 3,
        'contenido_rango_etario'    => 14,
        'contenido_discapacidad'    => 15,
        'contenido_hecho'           => 10,
        'contenido_responsable'     => 17,
        'contenido_practica'        => 20,
    ];

    /**
     * Parsea el CSV y devuelve array de expedientes agrupados por ID.
     *
     * Formato de retorno:
     * [
     *   'id_csv' => string,
     *   'datos'  => array (columnas fusionadas, más completo gana),
     *   'personas' => array (una por nombre detectado),
     *   'archivos_csv' => array (rutas de archivos del CSV sin resolver),
     *   'filas_originales' => array (filas raw),
     * ]
     *
     * ADVERTENCIA: carga todos los grupos en memoria a la vez. Para CSVs
     * grandes use parsearCsvCallback().
     */
    public function parsearCsv(string $rutaAbsoluta): array
    {
        $expedientes = [];
        $this->parsearCsvCallback($rutaAbsoluta, function (array $grupo) use (&$expedientes) {
            $expedientes[] = $grupo;
        });
        return $expedientes;
    }

    /**
     * Parsea el CSV en modo streaming: llama a $onGrupo($grupo) por cada
     * expediente completo y libera su memoria antes de continuar.
     *
     * IMPORTANTE: asume que todas las filas de un mismo ID son contiguas en
     * el CSV (orden típico de cualquier exportación de hoja de cálculo). Si
     * las filas de un mismo ID están dispersas, ese grupo se procesará en
     * múltiples llamadas (una por bloque contiguo).
     *
     * Retorna el número de grupos emitidos.
     */
    public function parsearCsvCallback(string $rutaAbsoluta, callable $onGrupo): int
    {
        $handle = fopen($rutaAbsoluta, 'r');
        if (!$handle) {
            throw new \RuntimeException("No se pudo abrir el archivo CSV.");
        }

        // Detectar BOM UTF-8
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Leer fila 1 (encabezados principales) – se descarta
        fgetcsv($handle, 0, ';', '"', '\\');
        // Leer fila 2 (sub-encabezados) – se descarta
        fgetcsv($handle, 0, ';', '"', '\\');

        $grupoActual = null;
        $idActual    = null;
        $total       = 0;

        while (($fila = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            // Normalizar: padding si hay menos columnas de las esperadas
            while (count($fila) < 82) {
                $fila[] = '';
            }

            $id_csv = trim($fila[0]);
            if ($id_csv === '') continue;

            if ($id_csv !== $idActual) {
                // Nuevo ID: emitir el grupo anterior si existe
                if ($grupoActual !== null) {
                    $onGrupo($grupoActual);
                    $total++;
                    $grupoActual = null;
                }
                $idActual    = $id_csv;
                $grupoActual = [
                    'id_csv'           => $id_csv,
                    'datos'            => $fila,
                    'personas'         => [],
                    'archivos_csv'     => [],
                    'filas_originales' => [],
                ];
            } else {
                // Misma fila: fusionar — conservar el valor más completo por columna
                foreach ($fila as $i => $valor) {
                    if (trim($valor) !== '' && trim($grupoActual['datos'][$i] ?? '') === '') {
                        $grupoActual['datos'][$i] = $valor;
                    }
                }
            }

            $filaIdx = count($grupoActual['filas_originales']);
            $grupoActual['filas_originales'][] = $fila;

            $this->extraerPersonas($grupoActual, $fila);
            $this->extraerArchivos($grupoActual, $fila, $filaIdx);
        }

        // Emitir el último grupo pendiente
        if ($grupoActual !== null) {
            $onGrupo($grupoActual);
            $total++;
        }

        fclose($handle);

        return $total;
    }

    /**
     * Extrae personas de una fila y las añade al grupo.
     * Los nombres pueden venir separados por "|" o "," en la misma celda,
     * o en filas distintas del mismo ID.
     */
    private function extraerPersonas(array &$grupo, array $fila): void
    {
        $nombreRaw = trim($fila[26] ?? '');
        if ($nombreRaw === '' || strtolower($nombreRaw) === 'n/a') return;

        // Separar múltiples personas por | primero, luego por ,
        $nombres = preg_split('/\s*\|\s*/', $nombreRaw);
        if (count($nombres) === 1) {
            // Intentar separar por , solo si NO parece "Apellido, Nombre"
            $partes = preg_split('/\s*,\s*/', $nombreRaw);
            if (count($partes) > 1 && !$this->pareceNombreApellido($nombreRaw)) {
                $nombres = $partes;
            }
        }

        foreach ($nombres as $idx => $nombre) {
            $nombre = trim($nombre);
            if ($nombre === '') continue;

            // Evitar duplicar si ya existe ese nombre
            $yaExiste = collect($grupo['personas'])->contains(fn($p) => mb_strtolower($p['nombre']) === mb_strtolower($nombre));
            if ($yaExiste) continue;

            $grupo['personas'][] = [
                'nombre'                 => $nombre,
                'lugar_origen_depto'     => trim($fila[27] ?? ''),
                'lugar_origen_muni'      => trim($fila[28] ?? ''),
                'nombre_identitario'     => trim($fila[29] ?? ''),
                'poblacion'              => trim($fila[30] ?? ''),
                'ocupacion'              => trim($fila[31] ?? ''),
                'sexo'                   => trim($fila[32] ?? ''),
                'identidad_genero'       => trim($fila[33] ?? ''),
                'orientacion_sexual'     => trim($fila[34] ?? ''),
                'etnia'                  => trim($fila[35] ?? ''),
                'rango_etario'           => trim($fila[36] ?? ''),
                'edad'                   => trim($fila[37] ?? ''),
                'discapacidad'           => trim($fila[38] ?? ''),
                'consent_tiene'          => trim($fila[39] ?? ''),
                'consent_menor'          => trim($fila[40] ?? ''),
                'consent_autoriza'       => trim($fila[41] ?? ''),
                'consent_grabacion'      => trim($fila[42] ?? ''),
                'consent_procesamiento'  => trim($fila[43] ?? ''),
                'consent_uso'            => trim($fila[44] ?? ''),
                'consent_riesgo'         => trim($fila[45] ?? ''),
                'consent_datos_pers'     => trim($fila[46] ?? ''),
                'consent_datos_sens'     => trim($fila[47] ?? ''),
                'consent_obs'            => trim($fila[48] ?? ''),
                'prueba_dano_privados'   => trim($fila[65] ?? ''),
                'prueba_dano_publicos'   => trim($fila[66] ?? ''),
                'prueba_dano_intelig'    => trim($fila[67] ?? ''),
                'prueba_dano_nna'        => trim($fila[68] ?? ''),
            ];
        }
    }

    /**
     * Heurística: "García, Juan" suele tener solo una coma y la parte derecha
     * es un nombre corto (≤ 2 palabras). Si hay más de una coma, probablemente
     * es una lista de personas.
     */
    private function pareceNombreApellido(string $texto): bool
    {
        $comas = substr_count($texto, ',');
        if ($comas > 1) return false;
        if ($comas === 0) return true;

        [$izq, $der] = explode(',', $texto, 2);
        return str_word_count(trim($der)) <= 2;
    }

    /**
     * Extrae las rutas de archivo de una fila y las añade al grupo.
     *
     * $filaIdx: índice de la fila dentro del grupo (0-based). Permite al job
     * emparejar cada transcripción con el audio del mismo renglón del CSV.
     */
    private function extraerArchivos(array &$grupo, array $fila, int $filaIdx = 0): void
    {
        $candidatos = [
            ['ruta' => trim($fila[2]  ?? ''), 'columna' => 'Ruta de resguardo (audio/video)', 'id_tipo' => 310],
            ['ruta' => trim($fila[76] ?? ''), 'columna' => 'Consentimiento',                   'id_tipo' => 311],
            ['ruta' => trim($fila[77] ?? ''), 'columna' => 'Transcripción',                    'id_tipo' => 313],
            ['ruta' => trim($fila[78] ?? ''), 'columna' => 'Otros',                            'id_tipo' => 315],
        ];

        foreach ($candidatos as $c) {
            if ($c['ruta'] === '' || strtolower($c['ruta']) === 'n/a') continue;

            // Evitar duplicar la misma ruta dentro del mismo grupo
            $yaExiste = collect($grupo['archivos_csv'])->contains(fn($a) => $a['ruta'] === $c['ruta']);
            if ($yaExiste) continue;

            $grupo['archivos_csv'][] = array_merge($c, ['fila_idx' => $filaIdx]);
        }
    }

    // -------------------------------------------------------------------------
    // Resolución de rutas NAS → Linux
    // -------------------------------------------------------------------------

    /**
     * Convierte una ruta del CSV a ruta Linux absoluta.
     *
     * Estrategia (en orden):
     *   1. Si coincide con algún prefijo UNC configurado → mapping NAS.
     *   2. Si ya es ruta absoluta Linux (empieza con /) → se usa tal cual.
     *   3. Si es un nombre de archivo simple (sin separadores) y se proporcionó
     *      $dirLocal → se resuelve dentro de esa carpeta local (p. ej. archivos
     *      depositados vía SFTP en storage/app/importaciones/transcripciones/).
     *   4. En cualquier otro caso → se devuelve con barras convertidas a Linux.
     *
     * $mappings: array de ['unc' => '\\\\servidor\\share', 'linux' => '/mnt/punto']
     * $dirLocal: ruta Linux absoluta usada como base para nombres de archivo simples.
     */
    public function resolverRuta(string $ruta, array $mappings, string $dirLocal = ''): string
    {
        if ($ruta === '') return '';

        // 1. Intentar mapping UNC
        foreach ($mappings as $m) {
            $unc   = rtrim(str_replace('/', '\\', $m['unc']), '\\');
            $linux = rtrim($m['linux'], '/');

            $rutaNorm = str_replace('/', '\\', $ruta);

            if (stripos($rutaNorm, $unc) === 0) {
                $resto = substr($rutaNorm, strlen($unc));
                $resto = str_replace('\\', '/', $resto);
                return $linux . $resto;
            }
        }

        // 2. Ruta absoluta Linux
        if (str_starts_with($ruta, '/')) {
            return $ruta;
        }

        // 3. Nombre de archivo simple (sin barras) → carpeta local
        $rutaLinux = str_replace('\\', '/', $ruta);
        if ($dirLocal !== '' && !str_contains($rutaLinux, '/')) {
            return rtrim($dirLocal, '/') . '/' . $rutaLinux;
        }

        // 4. Fallback: convertir separadores
        return $rutaLinux;
    }

    /**
     * Verifica si un archivo existe en el sistema de archivos Linux.
     * Retorna ['existe' => bool, 'tamano' => int|null, 'es_directorio' => bool]
     */
    public function verificarArchivo(string $rutaLinux): array
    {
        if ($rutaLinux === '') {
            return ['existe' => false, 'tamano' => null, 'es_directorio' => false];
        }

        if (is_dir($rutaLinux)) {
            return ['existe' => true, 'tamano' => null, 'es_directorio' => true];
        }

        if (file_exists($rutaLinux)) {
            return ['existe' => true, 'tamano' => filesize($rutaLinux), 'es_directorio' => false];
        }

        return ['existe' => false, 'tamano' => null, 'es_directorio' => false];
    }

    /**
     * Determina si un archivo necesita conversión a m4a (> 500 MB y es audio/video).
     */
    public function necesitaConversion(string $rutaLinux, int $tamano): bool
    {
        if ($tamano <= 500 * 1024 * 1024) return false;

        $ext = strtolower(pathinfo($rutaLinux, PATHINFO_EXTENSION));
        return in_array($ext, self::EXT_AUDIO_VIDEO);
    }

    // -------------------------------------------------------------------------
    // Extracción de valores únicos de catálogo para mapeo
    // -------------------------------------------------------------------------

    /**
     * Recorre todos los expedientes y extrae los valores únicos por campo de catálogo.
     *
     * Retorna:
     * [
     *   'dependencia'         => ['Dirección de Archivo...', ...],
     *   'tipo_testimonio'     => ['Entrevista Individual', ...],
     *   ...
     * ]
     */
    public function extraerValoresUnicos(array $expedientes): array
    {
        $columnasCatalogo = [
            'dependencia'            => 9,
            'equipo_estrategia'      => 10,
            'tipo_testimonio'        => 12,
            'formato'                => 13,
            'modalidad'              => 17,
            'idioma'                 => 20,
            'necesidad_reparacion'   => 21,
            'areas_compatibles'      => 22,
            'poblacion'              => 30,
            'ocupacion'              => 31,
            'sexo'                   => 32,
            'identidad_genero'       => 33,
            'orientacion_sexual'     => 34,
            'etnia'                  => 35,
            'rango_etario'           => 36,
            'discapacidad'           => 38,
            'contenido_hecho'        => 57,
            'contenido_responsable'  => 58,
            'contenido_practica'     => 73,
        ];

        $columnasMúltiples = [
            'formato', 'modalidad', 'necesidad_reparacion', 'areas_compatibles',
            'poblacion', 'ocupacion', 'contenido_hecho', 'contenido_responsable',
            'contenido_practica',
        ];

        $valores = [];

        foreach ($columnasCatalogo as $campo => $colIdx) {
            if ($colIdx === null) continue;
            $valores[$campo] = [];

            foreach ($expedientes as $exp) {
                $datos = $exp['datos'];
                $celda = trim($datos[$colIdx] ?? '');
                if ($celda === '' || strtolower($celda) === 'n/a') continue;

                if (in_array($campo, $columnasMúltiples)) {
                    // Puede ser lista separada por coma o punto y coma
                    $items = preg_split('/\s*[;,|]\s*/', $celda);
                    foreach ($items as $item) {
                        $item = trim($item);
                        if ($item !== '' && !in_array($item, $valores[$campo])) {
                            $valores[$campo][] = $item;
                        }
                    }
                } else {
                    if (!in_array($celda, $valores[$campo])) {
                        $valores[$campo][] = $celda;
                    }
                }
            }

            // También recolectar de las personas para campos de persona
        }

        // Campos de personas (desde el array de personas de cada expediente)
        $camposPersona = [
            'poblacion', 'ocupacion', 'sexo', 'identidad_genero',
            'orientacion_sexual', 'etnia', 'rango_etario', 'discapacidad',
        ];

        foreach ($expedientes as $exp) {
            foreach ($exp['personas'] as $persona) {
                foreach ($camposPersona as $campo) {
                    $celda = trim($persona[$campo] ?? '');
                    if ($celda === '' || strtolower($celda) === 'n/a') continue;
                    $items = preg_split('/\s*[;,|]\s*/', $celda);
                    foreach ($items as $item) {
                        $item = trim($item);
                        if ($item !== '' && !in_array($item, $valores[$campo])) {
                            $valores[$campo][] = $item;
                        }
                    }
                }
            }
        }

        // Campos de contenido del testimonio (cols 49-56): no están en columnasCatalogo
        $columnasContenido = [
            'contenido_poblacion'    => 49,
            'contenido_ocupacion'    => 50,
            'contenido_sexo'         => 51,
            'contenido_identidad'    => 52,
            'contenido_orientacion'  => 53,
            'contenido_etnia'        => 54,
            'contenido_rango_etario' => 55,
            'contenido_discapacidad' => 56,
        ];

        foreach ($columnasContenido as $campo => $colIdx) {
            $valores[$campo] = [];
            foreach ($expedientes as $exp) {
                $celda = trim($exp['datos'][$colIdx] ?? '');
                if ($celda === '' || strtolower($celda) === 'n/a') continue;
                $items = preg_split('/\s*[;,|]\s*/', $celda);
                foreach ($items as $item) {
                    $item = trim($item);
                    if ($item !== '' && !in_array($item, $valores[$campo])) {
                        $valores[$campo][] = $item;
                    }
                }
            }
        }

        // Geografía (departamentos y municipios)
        // Las celdas pueden tener múltiples valores separados por " | " (deptos)
        // o por ", " (munis). Se dividen para que cada nombre individual aparezca
        // como una entrada de mapeo en el paso 2.
        $valores['lugar_depto']    = [];
        $valores['lugar_muni_raw'] = []; // formato "primer_depto||muni"

        $colParejas = [[15, 16], [27, 28], [62, 63]];
        foreach ($expedientes as $exp) {
            $datos = $exp['datos'];
            foreach ($colParejas as [$cDepto, $cMuni]) {
                $deptoRaw = trim($datos[$cDepto] ?? '');
                $muniRaw  = trim($datos[$cMuni]  ?? '');

                // Dividir departamentos por "|"
                if ($deptoRaw !== '' && strtolower($deptoRaw) !== 'n/a') {
                    foreach (preg_split('/\s*\|\s*/', $deptoRaw) as $depto) {
                        $depto = trim($depto);
                        if ($depto !== '' && !in_array($depto, $valores['lugar_depto'])) {
                            $valores['lugar_depto'][] = $depto;
                        }
                    }
                }

                // Emparejar municipios con departamentos por índice posicional
                // (depto[0]↔muni[0], depto[1]↔muni[1], etc.), replicando la
                // misma lógica de resolverLugares() en el Job. Si hay más
                // municipios que departamentos se reutiliza el último departamento.
                if ($muniRaw !== '' && strtolower($muniRaw) !== 'n/a') {
                    $deptoParts = array_values(array_filter(
                        array_map('trim', preg_split('/\s*\|\s*/', $deptoRaw))
                    ));
                    $muniParts = array_values(array_filter(
                        array_map('trim', preg_split('/\s*,\s*/', $muniRaw))
                    ));
                    $count = max(count($deptoParts), count($muniParts), 1);

                    for ($i = 0; $i < $count; $i++) {
                        $depto = $deptoParts[$i] ?? ($deptoParts[count($deptoParts) - 1] ?? '');
                        $muni  = $muniParts[$i]  ?? '';
                        if ($muni === '') continue;
                        $key = "$depto||$muni";
                        if (!in_array($key, $valores['lugar_muni_raw'])) {
                            $valores['lugar_muni_raw'][] = $key;
                        }
                    }
                }
            }
        }

        // Eliminar campos sin valores
        return array_filter($valores, fn($v) => count($v) > 0);
    }

    /**
     * Pre-sugiere mapeos fuzzy comparando los valores del CSV con las descripciones
     * del catálogo correspondiente (ignorando mayúsculas y tildes).
     *
     * Retorna:
     * [
     *   'dependencia' => ['Dirección de Archivo...' => 42, ...],
     *   ...
     * ]
     */
    public function sugerirMapeos(array $valoresUnicos): array
    {
        $sugerencias = [];

        // -----------------------------------------------------------------
        // Catálogos generales — con niveles de confianza
        // -----------------------------------------------------------------
        foreach (self::CATALOGOS as $campo => $idCat) {
            if (!isset($valoresUnicos[$campo])) continue;

            $items = CatItem::where('id_cat', $idCat)->get()->mapWithKeys(function ($item) {
                return [$item->id_item => $this->normalizar($item->descripcion)];
            });

            foreach ($valoresUnicos[$campo] as $valorCsv) {
                $norm = $this->normalizar($valorCsv);
                $encontrado = $items->search(fn($desc) => $desc === $norm);
                if ($encontrado !== false) {
                    $sugerencias[$campo][$valorCsv] = $encontrado;
                    $sugerencias["{$campo}_confianza"][$valorCsv] = 'exacto';
                } else {
                    $encontrado = $items->search(fn($desc) => str_contains($desc, $norm));
                    if ($encontrado !== false) {
                        $sugerencias[$campo][$valorCsv] = $encontrado;
                        $sugerencias["{$campo}_confianza"][$valorCsv] = 'parcial';
                    } else {
                        $sugerencias[$campo][$valorCsv] = null;
                        $sugerencias["{$campo}_confianza"][$valorCsv] = null;
                    }
                }
            }
        }

        // -----------------------------------------------------------------
        // Geografía – departamentos
        // -----------------------------------------------------------------
        if (!empty($valoresUnicos['lugar_depto'])) {
            $deptos = Geo::where('nivel', 2)->get()->mapWithKeys(fn($g) => [$g->id_geo => $this->normalizar($g->descripcion)]);
            foreach ($valoresUnicos['lugar_depto'] as $val) {
                $norm = $this->normalizar($val);
                $encontrado = $deptos->search(fn($d) => $d === $norm);
                $sugerencias['lugar_depto'][$val] = $encontrado !== false ? $encontrado : null;
                $sugerencias['lugar_depto_confianza'][$val] = $encontrado !== false ? 'exacto' : null;
            }
        }

        // -----------------------------------------------------------------
        // Geografía – municipios (con contexto de departamento padre)
        // -----------------------------------------------------------------
        if (!empty($valoresUnicos['lugar_muni_raw'])) {
            // Lookup de nombre de depto → id_geo desde las sugerencias ya calculadas
            $deptoNameToId = [];
            foreach (($sugerencias['lugar_depto'] ?? []) as $nombre => $idGeo) {
                if ($idGeo) $deptoNameToId[$nombre] = $idGeo;
            }

            // Cargar todos los municipios una sola vez, agrupados por departamento
            $allMunis     = Geo::where('nivel', 3)->get();
            $munisByDepto = $allMunis->groupBy('id_padre');

            foreach ($valoresUnicos['lugar_muni_raw'] as $key) {
                [$deptoNombre, $muniNombre] = explode('||', $key, 2);
                $normMuni       = $this->normalizar($muniNombre);
                $idDeptoSugerido = $deptoNameToId[$deptoNombre] ?? null;
                $encontrado     = null;
                $confianza      = null;

                // Fase 1: buscar dentro del departamento sugerido
                if ($idDeptoSugerido && isset($munisByDepto[$idDeptoSugerido])) {
                    foreach ($munisByDepto[$idDeptoSugerido] as $geo) {
                        if ($this->normalizar($geo->descripcion) === $normMuni) {
                            $encontrado = $geo->id_geo;
                            $confianza  = 'exacto';
                            break;
                        }
                    }
                    if (!$encontrado) {
                        foreach ($munisByDepto[$idDeptoSugerido] as $geo) {
                            $normDesc = $this->normalizar($geo->descripcion);
                            if (str_contains($normDesc, $normMuni) || str_contains($normMuni, $normDesc)) {
                                $encontrado = $geo->id_geo;
                                $confianza  = 'parcial';
                                break;
                            }
                        }
                    }
                }

                // Fase 2: búsqueda global (todos los departamentos)
                if (!$encontrado) {
                    foreach ($allMunis as $geo) {
                        if ($this->normalizar($geo->descripcion) === $normMuni) {
                            $encontrado = $geo->id_geo;
                            // Si cayó en otro depto distinto al indicado en el CSV, marcar
                            $confianza = ($idDeptoSugerido && $geo->id_padre != $idDeptoSugerido)
                                ? 'otro_depto'
                                : 'exacto';
                            break;
                        }
                    }
                }
                if (!$encontrado) {
                    foreach ($allMunis as $geo) {
                        $normDesc = $this->normalizar($geo->descripcion);
                        if (str_contains($normDesc, $normMuni) || str_contains($normMuni, $normDesc)) {
                            $encontrado = $geo->id_geo;
                            $confianza  = 'parcial';
                            break;
                        }
                    }
                }

                $sugerencias['lugar_muni'][$key]            = $encontrado;
                $sugerencias['lugar_muni_confianza'][$key]   = $confianza;
            }
        }

        return $sugerencias;
    }

    /**
     * Normaliza un string para comparación fuzzy (minúsculas sin tildes).
     */
    public function normalizar(string $str): string
    {
        $str = mb_strtolower(trim($str));
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i',
            'ò' => 'o', 'ù' => 'u',
        ];
        return strtr($str, $map);
    }

    // -------------------------------------------------------------------------
    // Conversión de valores booleanos del CSV
    // -------------------------------------------------------------------------

    /**
     * Convierte "Sí"/"Si"/"No"/"1"/"0" a entero 0/1.
     */
    public function parseBool(string $valor, int $defecto = 0): int
    {
        $v = mb_strtolower(trim($valor));
        if (in_array($v, ['sí', 'si', 's', 'yes', 'y', '1', 'true'])) return 1;
        if (in_array($v, ['no', 'n', '0', 'false']))                   return 0;
        return $defecto;
    }

    /**
     * Parsea una fecha en varios formatos comunes al formato Y-m-d o null.
     */
    public function parseFecha(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '' || strtolower($valor) === 'n/a') return null;

        // Ignorar fechas con formato 00/00/YYYY (desconocido)
        if (preg_match('/^00\/00\//', $valor)) return null;

        $formatos = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y', 'Y/m/d'];
        foreach ($formatos as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $valor);
            if ($dt !== false) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Divide un campo multi-valor por separadores , o ; en un array limpio.
     */
    public function parseLista(string $valor): array
    {
        if (trim($valor) === '' || strtolower(trim($valor)) === 'n/a') return [];
        $items = preg_split('/\s*[;,|]\s*/', $valor);
        return array_values(array_filter(array_map('trim', $items)));
    }
}
