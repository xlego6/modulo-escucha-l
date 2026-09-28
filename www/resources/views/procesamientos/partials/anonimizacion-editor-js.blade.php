{{--
    Logica del editor visual de anonimizacion (compartido: editar-anonimizacion-asignada / revisar-anonimizacion).
    Parametros: $entidades, $entrevista, $asignacion

    Modelo: cada etiqueta es {id, db_id, text, type, start, end, cubierta, manual, grupo}.
    Las etiquetas del mismo tipo con el mismo "grupo" comparten reemplazo [TIPO_N].
    El grupo es, por defecto, el texto normalizado (sin acentos, minusculas, sin
    puntuacion): "Carlos Hernandez," y "Carlos Hernández." son la misma persona.
    Al unir etiquetas ("Aleja" -> "Alejandra Hernández") se guarda el grupo destino.
    La numeracion se recalcula siempre por orden de primera aparicion.
--}}
<script>
var entidades = @json($entidades);
var textoOriginal = @json($entrevista->getTextoParaProcesamiento() ?? '');
var TEXTOS_EXCLUIDOS = new Set(@json(\App\Models\EntidadDetectada::textosExcluidos()));
var ORDEN_TIPOS = @json(array_keys(\App\Models\EntidadDetectada::tipos()));
var CLAVE_ULTIMA_POSICION = 'anon-ultima-pos-{{ $asignacion->id_asignacion }}';

var estadoEntidades = [];
var siguienteId = 0;

// Seleccion en "Texto Etiquetado" pendiente de etiquetar
var seleccionActual = null;
var menuEtiquetarRecienAbierto = false;
// Etiqueta sobre la que se abrio un menu contextual (id) y grupo a unir en el modal
var entidadContextual = null;
var grupoAUnir = null;

// Deshacer / rehacer: pilas de instantaneas de estadoEntidades
var historialDeshacer = [];
var historialRehacer = [];
var MAX_HISTORIAL = 200;
var hayCambiosSinGuardar = false;

// Posicion (start) de la ultima etiqueta agregada/tocada: al guardar se
// recuerda para volver a ese punto del texto tras recargar la pagina.
var ultimaPosicion = null;

var syncingScroll = false;
var suspenderSyncScrollHasta = 0;

// Indice del texto original "plegado" para busquedas flexibles (ver construirIndicePlegado)
var indicePlegado = null;
var iniciosLinea = null;

$(document).ready(function() {
    inicializarEditorVisual();
    restaurarUltimaPosicion();

    // --- Etiquetar: seleccion de texto en el panel "Texto Etiquetado" ---
    $('#texto-original-marcado').on('mouseup', function(e) {
        var selection = window.getSelection();
        var textoSeleccionado = selection.toString();
        if (textoSeleccionado.trim().length === 0) return;

        if (/[\r\n]/.test(textoSeleccionado.trim())) {
            avisar('warning', 'La selección abarca más de un párrafo. Seleccione texto dentro de un mismo párrafo.');
            return;
        }

        var instancias = buscarInstancias(textoSeleccionado);
        if (instancias.length === 0) {
            avisar('warning', 'No se pudo ubicar "' + textoSeleccionado.trim() + '" en el texto. Seleccione solo palabras (sin la etiqueta de otra entidad).');
            return;
        }

        var elegida = instanciaDeLaSeleccion(selection, instancias);
        seleccionActual = {
            text: textoOriginal.substring(elegida.start, elegida.end),
            start: elegida.start,
            end: elegida.end,
            instancias: instancias
        };
        mostrarMenu('#entity-menu', e, 160, 360);
        // El mismo gesto de seleccion dispara un "click" justo despues del
        // mouseup: se absorbe una sola vez para que no cierre el menu que
        // acaba de abrirse, sin bloquear clics posteriores para cerrarlo.
        menuEtiquetarRecienAbierto = true;
    });

    // Ocultar menus al hacer clic fuera
    $(document).on('click', function(e) {
        if (menuEtiquetarRecienAbierto) {
            menuEtiquetarRecienAbierto = false;
        } else if (!$(e.target).closest('#entity-menu').length) {
            $('#entity-menu').removeClass('show');
            seleccionActual = null;
        }
        if (!$(e.target).closest('#entity-context-menu').length) {
            $('#entity-context-menu').removeClass('show');
        }
        if (!$(e.target).closest('#entity-original-context-menu').length) {
            $('#entity-original-context-menu').removeClass('show');
        }
    });

    // --- Scroll sincronizado entre columnas ---
    $('#texto-original-marcado').on('scroll', function() { sincronizarScroll(this, $('#editor-visual')[0]); });
    $('#editor-visual').on('scroll', function() { sincronizarScroll(this, $('#texto-original-marcado')[0]); });

    // --- Clic en una etiqueta del Texto Anonimizado: cubrir/descubrir ---
    $('#editor-visual').on('click', '.entity-clickable', function() {
        toggleEntidad(parseInt($(this).attr('data-id')));
    });

    // --- Menus contextuales (clic derecho) ---
    $(document).on('contextmenu', '#editor-visual .entity-clickable', function(e) {
        e.preventDefault();
        var ent = entidadPorId(parseInt($(this).attr('data-id')));
        if (!ent) return;
        entidadContextual = ent.id;
        $('#ctx-header').html(encabezadoEntidad(ent));
        mostrarMenu('#entity-context-menu', e, 230, 160);
    });

    $(document).on('contextmenu', '#texto-original-marcado .entity-original', function(e) {
        e.preventDefault();
        var ent = entidadPorId(parseInt($(this).attr('data-id')));
        if (!ent) return;
        entidadContextual = ent.id;
        $('#ctx-original-header').html(encabezadoEntidad(ent));
        mostrarMenu('#entity-original-context-menu', e, 230, 110);
    });

    $('#ctx-cubrir-todas, #ctx-descubrir-todas').on('click', function() {
        var ent = entidadPorId(entidadContextual);
        $('#entity-context-menu').removeClass('show');
        if (!ent) return;
        var cubrir = this.id === 'ctx-cubrir-todas';
        registrarCambio();
        var clave = claveGrupo(ent);
        estadoEntidades.forEach(function(e) {
            if (e.type === ent.type && claveGrupo(e) === clave) e.cubierta = cubrir;
        });
        ultimaPosicion = ent.start;
        refrescar();
    });

    $('.ctx-unir').on('click', function() {
        var ent = entidadPorId(entidadContextual);
        $('.entity-menu').removeClass('show');
        if (ent) abrirModalUnir(ent.type, claveGrupo(ent));
    });

    $('#ctx-quitar-etiqueta').on('click', function() {
        $('#entity-original-context-menu').removeClass('show');
        quitarVariante(entidadContextual);
    });

    // --- Resaltado sincronizado (mismo grupo en ambos paneles y en la lista) ---
    $(document).on('mouseenter', '#texto-original-marcado .entity-original, #editor-visual .entity-clickable, #resumen-entidades .etiqueta-item', function() {
        marcarResaltadoSincronizado($(this).attr('data-type'), $(this).attr('data-grupo'), true);
    });
    $(document).on('mouseleave', '#texto-original-marcado .entity-original, #editor-visual .entity-clickable, #resumen-entidades .etiqueta-item', function() {
        marcarResaltadoSincronizado($(this).attr('data-type'), $(this).attr('data-grupo'), false);
    });

    // --- Tarjeta "Etiquetas asignadas" (se re-renderiza: eventos delegados) ---
    $('#resumen-entidades').on('click', '.btn-eliminar-grupo', function() {
        var $item = $(this).closest('.etiqueta-item');
        eliminarGrupo($item.attr('data-type'), $item.attr('data-grupo'));
    });
    $('#resumen-entidades').on('click', '.btn-unir-grupo', function() {
        var $item = $(this).closest('.etiqueta-item');
        abrirModalUnir($item.attr('data-type'), $item.attr('data-grupo'));
    });
    $('#resumen-entidades').on('click', '.btn-separar-variante', function() {
        var $item = $(this).closest('.etiqueta-item');
        separarVariante($item.attr('data-type'), $item.attr('data-grupo'), $(this).attr('data-variante'));
    });

    $('#btn-confirmar-unir').on('click', function() {
        var destino = $('#unir-destino').val();
        $('#modalUnirEtiqueta').modal('hide');
        if (grupoAUnir && destino) unirGrupos(grupoAUnir.type, grupoAUnir.grupo, destino);
    });

    // Expandir/contraer la tarjeta "Etiquetas asignadas"
    $('#btn-expandir-etiquetas').on('click', function() {
        var $body = $('#card-body-etiquetas');
        var $icon = $(this).find('i');
        var expandido = $body.data('expandido') === true;
        if (expandido) {
            $body.css({ 'max-height': '300px', 'overflow-y': 'auto' }).data('expandido', false);
            $icon.removeClass('fa-compress-alt').addClass('fa-expand-alt');
        } else {
            $body.css({ 'max-height': 'none', 'overflow-y': 'visible' }).data('expandido', true);
            $icon.removeClass('fa-expand-alt').addClass('fa-compress-alt');
        }
    });

    // --- Atajos de deshacer / rehacer ---
    $(document).on('keydown', function(e) {
        if (!(e.ctrlKey || e.metaKey) || e.altKey) return;
        if ($(e.target).is('input, textarea, select, [contenteditable]') || $('.modal.show').length) return;
        var tecla = (e.key || '').toLowerCase();
        if (tecla === 'z' && !e.shiftKey) {
            e.preventDefault();
            deshacer();
        } else if (tecla === 'y' || (tecla === 'z' && e.shiftKey)) {
            e.preventDefault();
            rehacer();
        }
    });

    // --- Guardar ---
    $('#formAnonimizacion').on('submit', function() {
        sincronizarConTextarea();

        var nuevasManuales = estadoEntidades.filter(function(ent) {
            return ent.manual && !ent.db_id;
        }).map(serializarEntidad);
        $('#input_entidades_manuales').val(JSON.stringify(nuevasManuales));

        $('#input_estado_entidades').val(JSON.stringify(estadoEntidades.map(serializarEntidad)));

        // Eliminadas: las que venian de la BD y ya no estan en el estado actual
        // (quitadas a mano, o descartadas al cargar por ser pronombres/rotulos).
        var idsActivos = new Set(estadoEntidades.map(function(e) { return e.db_id; }));
        var eliminadas = entidades.filter(function(ent) {
            return ent.id && !idsActivos.has(ent.id);
        }).map(function(ent) { return { id: ent.id }; });
        $('#input_entidades_eliminadas').val(JSON.stringify(eliminadas));

        if (ultimaPosicion !== null) {
            try { sessionStorage.setItem(CLAVE_ULTIMA_POSICION, String(ultimaPosicion)); } catch (err) {}
        }
        hayCambiosSinGuardar = false;
    });

    $(window).on('beforeunload', function() {
        if (hayCambiosSinGuardar) {
            return 'Tiene cambios sin guardar. ¿Desea salir de la pagina?';
        }
    });
});

// =====================================================
// MODELO
// =====================================================

function inicializarEditorVisual() {
    estadoEntidades = [];
    var gruposConocidos = {};

    entidades.forEach(function(ent) {
        if (!ent.text) return;
        var e = {
            id: siguienteId++,
            db_id: ent.id || null,
            text: ent.text,
            type: ent.type,
            start: ent.start || 0,
            end: ent.end || ((ent.start || 0) + ent.text.length),
            cubierta: !ent.excluir,
            manual: ent.manual || false,
            grupo: ent.grupo || null
        };
        if (!recortarEntidad(e)) return;
        // Pronombres y rotulos de hablante ("Usted", "Edo.") que marco el NER
        if (!e.manual && TEXTOS_EXCLUIDOS.has(normalizarTexto(e.text))) return;
        if (e.grupo) gruposConocidos[e.type + '|' + normalizarTexto(e.text)] = e.grupo;
        estadoEntidades.push(e);
    });

    // Una variante unida a otra etiqueta arrastra consigo las instancias de su
    // mismo texto que aun no tengan grupo propio.
    estadoEntidades.forEach(function(e) {
        if (!e.grupo) e.grupo = gruposConocidos[e.type + '|' + normalizarTexto(e.text)] || null;
    });

    estadoEntidades.sort(function(a, b) { return a.start - b.start; });
    renderizarEditorVisual();
    sincronizarConTextarea();
}

// Recorta el span de una etiqueta: solo la primera linea (el NER a veces une
// un nombre con el rotulo de hablante del parrafo siguiente, ej.
// "Doña Selmira\n\nEdo") y sin puntuacion/espacios en los extremos. Mismo
// criterio que EntidadDetectada::recortarSpan() en el backend.
function recortarEntidad(ent) {
    var texto = ent.text.split(/\r\n|\r|\n/)[0];
    var m = texto.match(/^([^\p{L}\p{N}]*)([\s\S]*?)[^\p{L}\p{N}]*$/u);
    if (!m || m[2] === '') return false;
    var start = ent.start + m[1].length;
    var end = start + m[2].length;
    if (textoOriginal.substring(start, end) === m[2]) {
        // No dejar una marca de formato cortada por el recorte
        // ("la *niña bonita*" no debe perder el "*" final).
        var ajustada = balancearMarcas(start, end);
        ent.start = ajustada.start;
        ent.end = ajustada.end;
        ent.text = textoOriginal.substring(ajustada.start, ajustada.end);
    } else {
        ent.start = start;
        ent.end = end;
        ent.text = m[2];
    }
    return true;
}

// Debe coincidir con EntidadDetectada::normalizar() del backend.
function normalizarTexto(s) {
    return String(s)
        .normalize('NFD').replace(/\p{Mn}+/gu, '')
        .toLowerCase()
        .replace(/[*_]/g, '')
        .replace(/[^\p{L}\p{N}]+/gu, ' ')
        .trim();
}

function claveGrupo(ent) {
    return ent.grupo || normalizarTexto(ent.text);
}

function entidadPorId(id) {
    return estadoEntidades.find(function(e) { return e.id === id; }) || null;
}

// Etiquetas que efectivamente se muestran: sin solapamientos, en orden.
function entidadesVisibles() {
    var visibles = [];
    var finAnterior = -1;
    estadoEntidades.slice().sort(function(a, b) { return a.start - b.start; }).forEach(function(ent) {
        if (ent.start >= finAnterior) {
            visibles.push(ent);
            finAnterior = ent.end;
        }
    });
    return visibles;
}

// Mismo numero para todo el grupo, asignado por orden de primera aparicion.
function recalcularReemplazos() {
    var numeros = {};
    var contadores = {};
    entidadesVisibles().forEach(function(ent) {
        var k = ent.type + '|' + claveGrupo(ent);
        if (!(k in numeros)) {
            contadores[ent.type] = (contadores[ent.type] || 0) + 1;
            numeros[k] = contadores[ent.type];
        }
        ent.reemplazo = '[' + ent.type + '_' + numeros[k] + ']';
    });
}

function serializarEntidad(ent) {
    var normal = normalizarTexto(ent.text);
    return {
        db_id: ent.db_id || null,
        text: ent.text,
        type: ent.type,
        start: ent.start,
        end: ent.end,
        cubierta: ent.cubierta,
        grupo: (ent.grupo && ent.grupo !== normal) ? ent.grupo : null,
        reemplazo: ent.reemplazo || null
    };
}

// =====================================================
// HISTORIAL (deshacer / rehacer)
// =====================================================

function registrarCambio() {
    historialDeshacer.push(JSON.stringify(estadoEntidades));
    if (historialDeshacer.length > MAX_HISTORIAL) historialDeshacer.shift();
    historialRehacer = [];
    hayCambiosSinGuardar = true;
}

function deshacer() {
    if (historialDeshacer.length === 0) return;
    historialRehacer.push(JSON.stringify(estadoEntidades));
    estadoEntidades = JSON.parse(historialDeshacer.pop());
    hayCambiosSinGuardar = true;
    refrescar();
}

function rehacer() {
    if (historialRehacer.length === 0) return;
    historialDeshacer.push(JSON.stringify(estadoEntidades));
    estadoEntidades = JSON.parse(historialRehacer.pop());
    hayCambiosSinGuardar = true;
    refrescar();
}

function actualizarBotonesHistorial() {
    $('#btn-deshacer').prop('disabled', historialDeshacer.length === 0);
    $('#btn-rehacer').prop('disabled', historialRehacer.length === 0);
}

// =====================================================
// ACCIONES
// =====================================================

function refrescar() {
    renderizarEditorVisual();
    sincronizarConTextarea();
}

function toggleEntidad(id) {
    var ent = entidadPorId(id);
    if (!ent) return;
    registrarCambio();
    ent.cubierta = !ent.cubierta;
    ultimaPosicion = ent.start;
    refrescar();
}

function cubrirTodas() {
    registrarCambio();
    estadoEntidades.forEach(function(ent) { ent.cubierta = true; });
    refrescar();
}

function descubrirTodas() {
    registrarCambio();
    estadoEntidades.forEach(function(ent) { ent.cubierta = false; });
    refrescar();
}

function agregarEntidad(tipo) {
    if (!seleccionActual) return;
    var sel = seleccionActual;
    $('#entity-menu').removeClass('show');
    seleccionActual = null;

    var instancias = sel.instancias.filter(function(inst) {
        return !existeEntidadEnPosicion(inst.start, inst.end);
    });
    if (instancias.length === 0) {
        avisar('warning', 'No se encontraron instancias nuevas de "' + sinMarcas(sel.text) + '"');
        return;
    }

    registrarCambio();

    // Si ya hay una etiqueta de este tipo con el mismo texto (o una variante
    // equivalente), las nuevas instancias se suman a su grupo.
    var normal = normalizarTexto(sel.text);
    var existente = estadoEntidades.find(function(e) {
        return e.type === tipo && normalizarTexto(e.text) === normal;
    });
    var grupo = existente ? existente.grupo : null;

    instancias.forEach(function(inst) {
        estadoEntidades.push({
            id: siguienteId++,
            db_id: null,
            text: textoOriginal.substring(inst.start, inst.end),
            type: tipo,
            start: inst.start,
            end: inst.end,
            cubierta: true,
            manual: true,
            grupo: grupo
        });
    });
    estadoEntidades.sort(function(a, b) { return a.start - b.start; });
    ultimaPosicion = sel.start;

    window.getSelection().removeAllRanges();
    refrescar();

    avisar('success', instancias.length === 1
        ? 'Entidad "' + sinMarcas(sel.text) + '" agregada como ' + tipo
        : instancias.length + ' instancias de "' + sinMarcas(sel.text) + '" agregadas como ' + tipo);
}

// Quita la etiqueta de un texto (todas sus instancias equivalentes: mismo tipo
// y mismo texto normalizado). Las demas variantes del grupo se conservan.
function quitarVariante(id) {
    var ent = entidadPorId(id);
    if (!ent) return;
    var normal = normalizarTexto(ent.text);
    registrarCambio();
    estadoEntidades = estadoEntidades.filter(function(e) {
        return !(e.type === ent.type && normalizarTexto(e.text) === normal);
    });
    ultimaPosicion = ent.start;
    refrescar();
    avisar('info', 'Etiqueta "' + sinMarcas(ent.text) + '" (' + ent.type + ') eliminada. Ctrl+Z para deshacer.');
}

function eliminarGrupo(tipo, grupo) {
    var miembros = estadoEntidades.filter(function(e) { return e.type === tipo && claveGrupo(e) === grupo; });
    if (miembros.length === 0) return;
    var etiqueta = miembros[0].reemplazo || tipo;
    registrarCambio();
    estadoEntidades = estadoEntidades.filter(function(e) { return miembros.indexOf(e) === -1; });
    ultimaPosicion = miembros[0].start;
    refrescar();
    avisar('info', 'Etiqueta ' + etiqueta + ' eliminada. Ctrl+Z para deshacer.');
}

function abrirModalUnir(tipo, grupo) {
    var resumen = resumenGrupos();
    var origen = resumen.find(function(g) { return g.type === tipo && g.clave === grupo; });
    if (!origen) return;
    grupoAUnir = { type: tipo, grupo: grupo };

    $('#unir-origen').html(badgeGrupo(origen) + ' ' + escapeHtml(textoVariantes(origen)));
    var $select = $('#unir-destino').empty();
    resumen.forEach(function(g) {
        if (g.type !== tipo || g.clave === grupo) return;
        $select.append($('<option>').val(g.clave).text(g.reemplazo + '  —  ' + textoVariantes(g)));
    });
    var hayOpciones = $select.children().length > 0;
    $select.closest('.form-group').toggleClass('d-none', !hayOpciones);
    $('#unir-sin-opciones').toggleClass('d-none', hayOpciones);
    $('#btn-confirmar-unir').prop('disabled', !hayOpciones);
    $('#modalUnirEtiqueta').modal('show');
}

function unirGrupos(tipo, origen, destino) {
    registrarCambio();
    var primera = null;
    estadoEntidades.forEach(function(e) {
        if (e.type === tipo && claveGrupo(e) === origen) {
            e.grupo = destino;
            if (primera === null || e.start < primera) primera = e.start;
        }
    });
    ultimaPosicion = primera;
    refrescar();
    var g = resumenGrupos().find(function(x) { return x.type === tipo && x.clave === destino; });
    avisar('success', 'Etiquetas unidas' + (g ? ' como ' + g.reemplazo : '') + '. Ctrl+Z para deshacer.');
}

// Saca una variante de su grupo: vuelve a tener etiqueta propia.
function separarVariante(tipo, grupo, variante) {
    registrarCambio();
    estadoEntidades.forEach(function(e) {
        if (e.type === tipo && claveGrupo(e) === grupo && normalizarTexto(e.text) === variante) {
            e.grupo = null;
        }
    });
    refrescar();
}

function existeEntidadEnPosicion(start, end) {
    return estadoEntidades.some(function(ent) {
        return start < ent.end && end > ent.start;
    });
}

// =====================================================
// BUSQUEDA FLEXIBLE
// =====================================================

// Un caracter "de palabra" incluye letras acentuadas/eñes (\p{L} es Unicode,
// a diferencia de \w de JS que solo reconoce ASCII). El guion bajo NO cuenta:
// en el texto es marca de subrayado markdown.
function esCaracterDePalabra(c) {
    return c !== undefined && /[\p{L}\p{N}]/u.test(c);
}

// Version del texto original sin acentos, en minusculas, sin marcas de
// formato (* _) y con espacios colapsados, con un mapa de cada caracter
// plegado a su posicion en el original. Permite que seleccionar "hola mundo"
// encuentre "hola *mundo*" (cursiva) o que "Hernandez" encuentre "Hernández".
function construirIndicePlegado() {
    var plegado = [];
    var mapa = [];
    for (var i = 0; i < textoOriginal.length; i++) {
        var c = textoOriginal[i];
        if (c === '*' || c === '_') continue;
        if (/\s/.test(c)) {
            if (plegado.length > 0 && plegado[plegado.length - 1] === ' ') continue;
            plegado.push(' ');
            mapa.push(i);
            continue;
        }
        var f = plegarCaracter(c);
        if (!f) continue;
        plegado.push(f);
        mapa.push(i);
    }
    return { texto: plegado.join(''), mapa: mapa };
}

function plegarCaracter(c) {
    return c.normalize('NFD').replace(/\p{Mn}+/gu, '').toLowerCase().charAt(0);
}

function plegarBusqueda(s) {
    var out = '';
    for (var i = 0; i < s.length; i++) {
        var c = s[i];
        if (c === '*' || c === '_') continue;
        if (/\s/.test(c)) {
            if (out.length > 0 && out[out.length - 1] !== ' ') out += ' ';
            continue;
        }
        out += plegarCaracter(c);
    }
    return out.replace(/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/gu, '');
}

// Todas las apariciones (palabra completa) del texto buscado, en posiciones
// del texto original.
function buscarInstancias(buscar) {
    if (!indicePlegado) indicePlegado = construirIndicePlegado();
    var q = plegarBusqueda(buscar);
    if (!q) return [];

    var instancias = [];
    var desde = 0;
    while (true) {
        var idx = indicePlegado.texto.indexOf(q, desde);
        if (idx === -1) break;
        desde = idx + 1;

        var start = indicePlegado.mapa[idx];
        var end = indicePlegado.mapa[idx + q.length - 1] + 1;
        // Solo palabra completa: evita que "Flor" marque dentro de "Floreciendo".
        if (esCaracterDePalabra(textoOriginal[start - 1]) || esCaracterDePalabra(textoOriginal[end])) continue;

        var ajustada = balancearMarcas(start, end);
        instancias.push(ajustada);
    }
    return instancias;
}

// Si la etiqueta corta una marca de formato por la mitad ("hola *mundo" con
// el "*" de cierre afuera), la extiende para incluir la marca que falta; si
// no, el texto anonimizado quedaria con un "*" suelto.
function balancearMarcas(start, end) {
    ['*', '_'].forEach(function(m) {
        var tramos = function() {
            var seg = textoOriginal.substring(start, end);
            var r = seg.match(m === '*' ? /\*+/g : /_+/g);
            return r ? r.length : 0;
        };
        if (tramos() % 2 === 0) return;
        if (textoOriginal[end] === m) {
            while (textoOriginal[end] === m) end++;
        } else if (textoOriginal[start - 1] === m) {
            while (textoOriginal[start - 1] === m) start--;
        }
    });
    return { start: start, end: end };
}

// De todas las apariciones, la que el usuario selecciono: la que cae en el
// mismo parrafo que la seleccion (cada parrafo renderizado lleva data-linea).
function instanciaDeLaSeleccion(selection, instancias) {
    if (!iniciosLinea) {
        iniciosLinea = [0];
        for (var i = 0; i < textoOriginal.length; i++) {
            if (textoOriginal[i] === '\n') iniciosLinea.push(i + 1);
        }
    }
    var nodo = selection.rangeCount ? selection.getRangeAt(0).startContainer : null;
    if (nodo && nodo.nodeType === 3) nodo = nodo.parentNode;
    var linea = parseInt($(nodo).closest('[data-linea]').attr('data-linea'));
    if (isNaN(linea)) return instancias[0];

    var desde = iniciosLinea[linea];
    var hasta = linea + 1 < iniciosLinea.length ? iniciosLinea[linea + 1] : textoOriginal.length;
    return instancias.find(function(inst) {
        return inst.start >= desde && inst.start < hasta;
    }) || instancias[0];
}

// =====================================================
// RENDER
// =====================================================

function renderizarEditorVisual() {
    recalcularReemplazos();

    if (!textoOriginal) {
        $('#editor-visual, #texto-original-marcado').html('<p class="text-muted text-center py-5">No hay texto disponible</p>');
        actualizarContadores();
        actualizarResumen();
        actualizarBotonesHistorial();
        return;
    }

    var htmlEditor = '';
    var htmlOriginal = '';
    var pos = 0;

    entidadesVisibles().forEach(function(ent) {
        var previo = escapeHtml(textoOriginal.substring(pos, ent.start));
        htmlEditor += previo;
        htmlOriginal += previo;
        pos = ent.end;

        var atributos = 'data-id="' + ent.id + '" data-type="' + ent.type + '" ' +
                        'data-grupo="' + escapeHtml(claveGrupo(ent)) + '"';
        // Por si acaso: nunca dejar un salto de linea dentro de un span (el
        // formateo por lineas lo partiria en dos y el HTML quedaria roto).
        var texto = escapeHtml(ent.text.replace(/[\r\n]+/g, ' '));

        if (ent.cubierta) {
            htmlEditor += '<span class="entity-clickable entity-cubierta" ' + atributos +
                          ' title="Clic para descubrir: ' + texto + '">' + escapeHtml(ent.reemplazo) + '</span>';
        } else {
            htmlEditor += '<span class="entity-clickable entity-descubierta entity-' + ent.type + '" ' + atributos +
                          ' title="Clic para cubrir como: ' + escapeHtml(ent.reemplazo) + '">' + texto + '</span>';
        }

        htmlOriginal += '<span class="entity-original entity-' + ent.type + '" ' + atributos +
                        ' title="Clic derecho: unir o quitar etiqueta">' + texto +
                        '<sup class="entity-original-label">' + escapeHtml(ent.reemplazo.replace(/^\[|\]$/g, '')) + '</sup>' +
                        '</span>';
    });
    var resto = escapeHtml(textoOriginal.substring(pos));
    htmlEditor += resto;
    htmlOriginal += resto;

    $('#editor-visual').html(formatearTextoAnonimizado(htmlEditor));
    $('#texto-original-marcado').html(formatearTextoAnonimizado(htmlOriginal));

    actualizarContadores();
    actualizarResumen();
    actualizarBotonesHistorial();
}

function actualizarContadores() {
    var cubiertas = estadoEntidades.filter(function(e) { return e.cubierta; }).length;
    $('#contador-cubiertas').text(cubiertas);
    $('#contador-descubiertas').text(estadoEntidades.length - cubiertas);

    var todas = estadoEntidades.length > 0 && cubiertas === estadoEntidades.length;
    var ninguna = estadoEntidades.length > 0 && cubiertas === 0;
    $('#btn-cubrir-todas').toggleClass('active', todas);
    $('#btn-descubrir-todas').toggleClass('active', ninguna);
}

function marcarResaltadoSincronizado(tipo, grupo, activar) {
    if (!tipo || grupo === undefined) return;
    $('#texto-original-marcado .entity-original, #editor-visual .entity-clickable').filter(function() {
        return this.getAttribute('data-type') === tipo && this.getAttribute('data-grupo') === grupo;
    }).toggleClass('entity-hover-sync', activar);
}

// Grupos de etiquetas (tipo + grupo) con sus variantes de texto y conteos.
function resumenGrupos() {
    var grupos = {};
    var lista = [];
    estadoEntidades.slice().sort(function(a, b) { return a.start - b.start; }).forEach(function(ent) {
        var clave = claveGrupo(ent);
        var k = ent.type + '|' + clave;
        if (!grupos[k]) {
            grupos[k] = { type: ent.type, clave: clave, reemplazo: null, count: 0, variantes: [], porVariante: {} };
            lista.push(grupos[k]);
        }
        var g = grupos[k];
        g.count++;
        if (!g.reemplazo && ent.reemplazo) g.reemplazo = ent.reemplazo;
        var normal = normalizarTexto(ent.text);
        if (!g.porVariante[normal]) {
            g.porVariante[normal] = { normal: normal, text: ent.text, count: 0 };
            g.variantes.push(g.porVariante[normal]);
        }
        g.porVariante[normal].count++;
    });

    lista.forEach(function(g) {
        if (!g.reemplazo) g.reemplazo = '[' + g.type + ']';
        g.numero = parseInt((g.reemplazo.match(/_(\d+)\]$/) || [0, 0])[1]);
    });
    lista.sort(function(a, b) {
        return (ORDEN_TIPOS.indexOf(a.type) - ORDEN_TIPOS.indexOf(b.type)) || (a.numero - b.numero);
    });
    return lista;
}

function textoVariantes(g) {
    return g.variantes.map(function(v) { return sinMarcas(v.text); }).join(', ');
}

function badgeGrupo(g) {
    return '<span class="badge entity-' + g.type + '">' + escapeHtml(g.reemplazo) + '</span>';
}

function encabezadoEntidad(ent) {
    return '<span class="badge entity-' + ent.type + '">' + escapeHtml(ent.reemplazo || ent.type) + '</span> &ldquo;' + escapeHtml(sinMarcas(ent.text)) + '&rdquo;';
}

function actualizarResumen() {
    var grupos = resumenGrupos();
    if (grupos.length === 0) {
        $('#resumen-entidades').html('<p class="text-muted text-center small py-3 mb-0">Sin etiquetas</p>');
        return;
    }

    var html = '';
    grupos.forEach(function(g) {
        var variantes = g.variantes.map(function(v) {
            var separar = (g.variantes.length > 1 && v.normal !== g.clave)
                ? '<button type="button" class="btn btn-link text-secondary btn-separar-variante" data-variante="' + escapeHtml(v.normal) + '" title="Separar: vuelve a tener etiqueta propia"><i class="fas fa-unlink"></i></button>'
                : '';
            return '<span class="etiqueta-variante">' + escapeHtml(sinMarcas(v.text)) + ' <span class="text-muted">(' + v.count + ')</span>' + separar + '</span>';
        }).join('');

        html += '<div class="etiqueta-item" data-type="' + g.type + '" data-grupo="' + escapeHtml(g.clave) + '">' +
                    '<div>' + badgeGrupo(g) + ' ' + variantes + '</div>' +
                    '<div class="etiqueta-acciones">' +
                        '<button type="button" class="btn btn-sm btn-link text-primary btn-unir-grupo" title="Unir con otra etiqueta (misma persona, lugar...)"><i class="fas fa-link"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-link text-danger btn-eliminar-grupo" title="Eliminar etiqueta"><i class="fas fa-trash"></i></button>' +
                    '</div>' +
                '</div>';
    });
    $('#resumen-entidades').html(html);
}

// Texto que generan las etiquetas actuales (va al campo texto_anonimizado).
function calcularTextoDesdeEntidades() {
    var texto = '';
    var pos = 0;
    entidadesVisibles().forEach(function(ent) {
        texto += textoOriginal.substring(pos, ent.start) + (ent.cubierta ? ent.reemplazo : ent.text);
        pos = ent.end;
    });
    return texto + textoOriginal.substring(pos);
}

function sincronizarConTextarea() {
    var texto = calcularTextoDesdeEntidades();
    $('#texto_anonimizado').val(texto);
    $('#charCount').text(texto.length);
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// Renderiza encabezados/listas/parrafos markdown sobre texto que ya trae
// spans de entidades insertados (igual formato que el editor de transcripcion).
// Cada bloque lleva data-linea (indice de linea en el texto original) para
// ubicar a que aparicion corresponde una seleccion.
function formatearTextoAnonimizado(html) {
    var lineas = html.split('\n');
    var out = '';
    var enLista = false;

    lineas.forEach(function(linea, i) {
        var m;
        var attr = ' data-linea="' + i + '"';
        if ((m = linea.match(/^### (.*)$/))) {
            if (enLista) { out += '</ul>'; enLista = false; }
            out += '<h4 class="preview-h4"' + attr + '>' + formatearInlineAnonimizado(m[1]) + '</h4>';
        } else if ((m = linea.match(/^## (.*)$/))) {
            if (enLista) { out += '</ul>'; enLista = false; }
            out += '<h3 class="preview-h3"' + attr + '>' + formatearInlineAnonimizado(m[1]) + '</h3>';
        } else if ((m = linea.match(/^# (.*)$/))) {
            if (enLista) { out += '</ul>'; enLista = false; }
            out += '<h2 class="preview-h2"' + attr + '>' + formatearInlineAnonimizado(m[1]) + '</h2>';
        } else if ((m = linea.match(/^[-*] (.*)$/))) {
            if (!enLista) { out += '<ul class="preview-ul">'; enLista = true; }
            out += '<li' + attr + '>' + formatearInlineAnonimizado(m[1]) + '</li>';
        } else {
            if (enLista) { out += '</ul>'; enLista = false; }
            out += linea.trim() === '' ? '<br>' : '<p' + attr + '>' + formatearInlineAnonimizado(linea) + '</p>';
        }
    });

    if (enLista) out += '</ul>';
    return out;
}

function formatearInlineAnonimizado(linea) {
    // Solo formatea los fragmentos de texto, nunca las etiquetas HTML de los
    // spans de entidades ya insertados (evita, p. ej., que un guion bajo dentro
    // de una clase como "entity-GRUPO_ARMADO" se confunda con subrayado).
    return linea.split(/(<[^>]+>)/g).map(function(parte, i) {
        if (i % 2 === 1) return parte;
        return parte
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/\b_(.+?)_\b/g, '<u>$1</u>');
    }).join('');
}

// =====================================================
// UTILIDADES DE UI
// =====================================================

function mostrarMenu(selector, e, ancho, alto) {
    var posX = e.clientX + 5, posY = e.clientY + 5;
    if (posX + ancho > window.innerWidth) posX = e.clientX - ancho - 5;
    if (posY + alto > window.innerHeight) posY = Math.max(5, e.clientY - alto - 5);
    $(selector).css({ top: posY, left: posX }).addClass('show');
}

function sincronizarScroll(origen, destino) {
    if (syncingScroll || Date.now() < suspenderSyncScrollHasta) return;
    syncingScroll = true;
    var pct = origen.scrollTop / (origen.scrollHeight - origen.clientHeight) || 0;
    destino.scrollTop = pct * (destino.scrollHeight - destino.clientHeight);
    syncingScroll = false;
}

// Tras guardar (la pagina se recarga), vuelve a la ultima etiqueta agregada
// o tocada en cualquiera de los dos paneles y la resalta un momento.
function restaurarUltimaPosicion() {
    var guardada = null;
    try {
        guardada = sessionStorage.getItem(CLAVE_ULTIMA_POSICION);
        sessionStorage.removeItem(CLAVE_ULTIMA_POSICION);
    } catch (err) {}
    if (guardada === null) return;
    var pos = parseInt(guardada);
    if (isNaN(pos)) return;

    var visibles = entidadesVisibles();
    if (visibles.length === 0) return;
    var ent = visibles.reduce(function(mejor, e) {
        return Math.abs(e.start - pos) < Math.abs(mejor.start - pos) ? e : mejor;
    });
    ultimaPosicion = ent.start;

    var $izq = $('#texto-original-marcado [data-id="' + ent.id + '"]');
    var $der = $('#editor-visual [data-id="' + ent.id + '"]');
    suspenderSyncScrollHasta = Date.now() + 500;
    centrarEn($('#texto-original-marcado')[0], $izq[0]);
    centrarEn($('#editor-visual')[0], $der[0]);
    if ($izq.length) $izq[0].scrollIntoView({ block: 'nearest' });

    $izq.add($der).addClass('entity-ultima');
    setTimeout(function() { $izq.add($der).removeClass('entity-ultima'); }, 3000);
}

function centrarEn(contenedor, elemento) {
    if (!contenedor || !elemento) return;
    var delta = elemento.getBoundingClientRect().top - contenedor.getBoundingClientRect().top;
    contenedor.scrollTop += delta - contenedor.clientHeight / 2;
}

// Texto para mostrar en listas/encabezados, sin marcas markdown (* _)
function sinMarcas(texto) {
    return String(texto).replace(/[*_]/g, '');
}

function avisar(tipo, mensaje) {
    if (typeof toastr !== 'undefined') toastr[tipo](mensaje);
}
</script>
