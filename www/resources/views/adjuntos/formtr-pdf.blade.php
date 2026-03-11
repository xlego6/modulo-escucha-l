<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page {
        margin: 2cm 2.5cm 2.5cm 2.5cm;
    }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10pt;
        color: #000;
        line-height: 1.4;
    }
    .encabezado-doc {
        border-bottom: 2px solid #000;
        margin-bottom: 14pt;
        padding-bottom: 8pt;
    }
    .encabezado-doc .codigo {
        font-size: 9pt;
        color: #555;
        text-align: right;
        margin-bottom: 4pt;
    }
    .encabezado-doc h1 {
        font-size: 13pt;
        font-weight: bold;
        text-align: center;
        margin: 0 0 2pt 0;
        text-transform: uppercase;
        letter-spacing: 0.5pt;
    }
    .encabezado-doc h2 {
        font-size: 10pt;
        font-weight: normal;
        text-align: center;
        margin: 0;
        color: #333;
    }
    table.metadatos {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 18pt;
    }
    table.metadatos td {
        padding: 4pt 6pt;
        vertical-align: top;
        font-size: 9.5pt;
        border: 0.5pt solid #ccc;
    }
    table.metadatos td.label {
        width: 45%;
        font-weight: bold;
        background-color: #f0f0f0;
        color: #222;
    }
    table.metadatos td.valor {
        width: 55%;
    }
    .seccion-transcripcion {
        margin-top: 10pt;
    }
    .seccion-transcripcion h3 {
        font-size: 11pt;
        font-weight: bold;
        border-bottom: 1pt solid #000;
        padding-bottom: 3pt;
        margin-bottom: 10pt;
        text-transform: uppercase;
    }
    .texto-transcripcion {
        font-family: 'Times New Roman', serif;
        font-size: 10.5pt;
        line-height: 1.7;
        text-align: justify;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .pie-pagina {
        position: fixed;
        bottom: -1cm;
        left: 0;
        right: 0;
        font-size: 8pt;
        color: #666;
        border-top: 0.5pt solid #ccc;
        padding-top: 4pt;
        text-align: center;
    }
</style>
</head>
<body>

<div class="pie-pagina">
    Centro Nacional de Memoria Histórica &mdash; {{ $codigo }} &mdash; {{ $tipoLabel }}
</div>

<div class="encabezado-doc">
    <div class="codigo">Código: {{ $codigo }}</div>
    <h1>Centro Nacional de Memoria Histórica</h1>
    <h2>Formato de Transcripción de Testimonio &mdash; {{ $tipoLabel }}</h2>
    @if($titulo)
    <h2 style="margin-top:4pt; font-size:9pt; color:#555;">{{ $titulo }}</h2>
    @endif
</div>

<table class="metadatos">
    <tr>
        <td class="label">Lugar de realización del testimonio:</td>
        <td class="valor">{{ $lugar }}</td>
    </tr>
    <tr>
        <td class="label">Medio de toma del testimonio:</td>
        <td class="valor">{{ $medio }}</td>
    </tr>
    <tr>
        <td class="label">Fecha de realización del testimonio:</td>
        <td class="valor">{{ $fechaRealizacion }}</td>
    </tr>
    <tr>
        <td class="label">Nombre del entrevistador:</td>
        <td class="valor">{{ $entrevistador }}</td>
    </tr>
    <tr>
        <td class="label">Duración del audio:</td>
        <td class="valor">{{ $duracion }}</td>
    </tr>
    <tr>
        <td class="label">Fecha de inicio de transcripción:</td>
        <td class="valor">{{ $fechaInicioTranscripcion }}</td>
    </tr>
    <tr>
        <td class="label">Fecha de finalización de transcripción:</td>
        <td class="valor">{{ $fechaFinTranscripcion }}</td>
    </tr>
    <tr>
        <td class="label">Nombre de transcriptor:</td>
        <td class="valor">{{ $nombreTranscriptor }}</td>
    </tr>
    <tr>
        <td class="label">Dependencia de la entrevista:</td>
        <td class="valor">{{ $dependencia }}</td>
    </tr>
    <tr>
        <td class="label">Tipo de testimonio:</td>
        <td class="valor">{{ $tipoTestimonio }}</td>
    </tr>
</table>

<div class="seccion-transcripcion">
    <h3>Transcripción</h3>
    <div class="texto-transcripcion">{{ $texto }}</div>
</div>

</body>
</html>
