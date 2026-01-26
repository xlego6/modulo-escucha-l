{{--
    JavaScript para la barra de herramientas del editor
    Uso: @include('partials.editor-toolbar-js')
--}}
<script>
// Historial para undo/redo por textarea
var editorHistory = {};
var editorHistoryIndex = {};

// Inicializar editor
function initEditor(targetId) {
    var $textarea = $('#' + targetId);
    if (!$textarea.length) return;

    // Inicializar historial
    editorHistory[targetId] = [$textarea.val()];
    editorHistoryIndex[targetId] = 0;

    // Actualizar estadisticas iniciales
    updateEditorStats(targetId);

    // Escuchar cambios
    $textarea.on('input', function() {
        updateEditorStats(targetId);
        saveToHistory(targetId);
    });

    // Atajos de teclado
    $textarea.on('keydown', function(e) {
        // Ctrl+B = Negrita
        if (e.ctrlKey && e.key === 'b') {
            e.preventDefault();
            editorAction('bold', targetId);
        }
        // Ctrl+I = Cursiva
        if (e.ctrlKey && e.key === 'i') {
            e.preventDefault();
            editorAction('italic', targetId);
        }
        // Ctrl+U = Subrayado
        if (e.ctrlKey && e.key === 'u') {
            e.preventDefault();
            editorAction('underline', targetId);
        }
        // Ctrl+H = Buscar/Reemplazar
        if (e.ctrlKey && e.key === 'h') {
            e.preventDefault();
            editorAction('find', targetId);
        }
        // Ctrl+Z = Deshacer
        if (e.ctrlKey && e.key === 'z' && !e.shiftKey) {
            e.preventDefault();
            editorAction('undo', targetId);
        }
        // Ctrl+Y o Ctrl+Shift+Z = Rehacer
        if ((e.ctrlKey && e.key === 'y') || (e.ctrlKey && e.shiftKey && e.key === 'z')) {
            e.preventDefault();
            editorAction('redo', targetId);
        }
        // Tab = insertar espacios
        if (e.key === 'Tab') {
            e.preventDefault();
            editorInsert('    ', targetId);
        }
    });
}

// Actualizar estadisticas
function updateEditorStats(targetId) {
    var $textarea = $('#' + targetId);
    var text = $textarea.val();
    var chars = text.length;
    var words = text.trim() ? text.trim().split(/\s+/).length : 0;

    $('#' + targetId + '-chars').text(chars.toLocaleString());
    $('#' + targetId + '-words').text(words.toLocaleString());
}

// Guardar en historial
function saveToHistory(targetId) {
    var $textarea = $('#' + targetId);
    var currentValue = $textarea.val();

    // No guardar si es igual al ultimo
    if (editorHistory[targetId][editorHistoryIndex[targetId]] === currentValue) return;

    // Eliminar estados futuros si estamos en medio del historial
    editorHistory[targetId] = editorHistory[targetId].slice(0, editorHistoryIndex[targetId] + 1);

    // Agregar nuevo estado
    editorHistory[targetId].push(currentValue);
    editorHistoryIndex[targetId]++;

    // Limitar historial a 50 estados
    if (editorHistory[targetId].length > 50) {
        editorHistory[targetId].shift();
        editorHistoryIndex[targetId]--;
    }
}

// Acciones del editor
function editorAction(action, targetId) {
    var $textarea = $('#' + targetId);
    var textarea = $textarea[0];
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = $textarea.val();
    var selectedText = text.substring(start, end);

    switch(action) {
        case 'bold':
            wrapSelection(targetId, '**', '**');
            break;

        case 'italic':
            wrapSelection(targetId, '*', '*');
            break;

        case 'underline':
            wrapSelection(targetId, '_', '_');
            break;

        case 'heading':
            insertAtLineStart(targetId, '## ');
            break;

        case 'list-ul':
            insertAtLineStart(targetId, '- ');
            break;

        case 'quote':
            insertAtLineStart(targetId, '> ');
            break;

        case 'timestamp':
            // Obtener tiempo del audio si existe
            var audioTime = getAudioTime();
            editorInsert('[' + audioTime + '] ', targetId);
            break;

        case 'find':
            $('#modalBuscarReemplazar-' + targetId).modal('show');
            setTimeout(function() {
                $('#find-text-' + targetId).focus();
            }, 300);
            break;

        case 'undo':
            if (editorHistoryIndex[targetId] > 0) {
                editorHistoryIndex[targetId]--;
                $textarea.val(editorHistory[targetId][editorHistoryIndex[targetId]]);
                updateEditorStats(targetId);
            }
            break;

        case 'redo':
            if (editorHistoryIndex[targetId] < editorHistory[targetId].length - 1) {
                editorHistoryIndex[targetId]++;
                $textarea.val(editorHistory[targetId][editorHistoryIndex[targetId]]);
                updateEditorStats(targetId);
            }
            break;
    }
}

// Envolver seleccion con marcadores
function wrapSelection(targetId, before, after) {
    var $textarea = $('#' + targetId);
    var textarea = $textarea[0];
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = $textarea.val();
    var selectedText = text.substring(start, end);

    if (selectedText) {
        var newText = text.substring(0, start) + before + selectedText + after + text.substring(end);
        $textarea.val(newText);
        textarea.selectionStart = start + before.length;
        textarea.selectionEnd = end + before.length;
    } else {
        // Sin seleccion, insertar marcadores y posicionar cursor entre ellos
        var newText = text.substring(0, start) + before + after + text.substring(end);
        $textarea.val(newText);
        textarea.selectionStart = textarea.selectionEnd = start + before.length;
    }

    $textarea.focus();
    updateEditorStats(targetId);
    saveToHistory(targetId);
}

// Insertar al inicio de la linea
function insertAtLineStart(targetId, prefix) {
    var $textarea = $('#' + targetId);
    var textarea = $textarea[0];
    var start = textarea.selectionStart;
    var text = $textarea.val();

    // Encontrar inicio de la linea
    var lineStart = text.lastIndexOf('\n', start - 1) + 1;

    // Insertar prefijo
    var newText = text.substring(0, lineStart) + prefix + text.substring(lineStart);
    $textarea.val(newText);

    // Posicionar cursor
    textarea.selectionStart = textarea.selectionEnd = start + prefix.length;
    $textarea.focus();
    updateEditorStats(targetId);
    saveToHistory(targetId);
}

// Insertar texto en posicion actual
function editorInsert(textToInsert, targetId) {
    var $textarea = $('#' + targetId);
    var textarea = $textarea[0];
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = $textarea.val();

    var newText = text.substring(0, start) + textToInsert + text.substring(end);
    $textarea.val(newText);
    textarea.selectionStart = textarea.selectionEnd = start + textToInsert.length;
    $textarea.focus();
    updateEditorStats(targetId);
    saveToHistory(targetId);
}

// Insertar hablante personalizado
function editorInsertCustomSpeaker(targetId) {
    var speaker = prompt('Nombre del hablante:');
    if (speaker && speaker.trim()) {
        editorInsert('[' + speaker.trim() + ']: ', targetId);
    }
}

// Obtener tiempo del audio (si hay reproductor)
function getAudioTime() {
    var $audio = $('audio').first();
    if ($audio.length && $audio[0].currentTime) {
        var seconds = Math.floor($audio[0].currentTime);
        var h = Math.floor(seconds / 3600);
        var m = Math.floor((seconds % 3600) / 60);
        var s = seconds % 60;
        return (h > 0 ? h.toString().padStart(2, '0') + ':' : '') +
               m.toString().padStart(2, '0') + ':' +
               s.toString().padStart(2, '0');
    }
    return '00:00';
}

// Buscar siguiente
function editorFindNext(targetId) {
    var $textarea = $('#' + targetId);
    var textarea = $textarea[0];
    var findText = $('#find-text-' + targetId).val();
    var caseSensitive = $('#case-sensitive-' + targetId).is(':checked');

    if (!findText) return;

    var text = $textarea.val();
    var searchText = caseSensitive ? text : text.toLowerCase();
    var searchFor = caseSensitive ? findText : findText.toLowerCase();

    // Buscar desde la posicion actual
    var startPos = textarea.selectionEnd;
    var foundPos = searchText.indexOf(searchFor, startPos);

    // Si no se encuentra, buscar desde el inicio
    if (foundPos === -1) {
        foundPos = searchText.indexOf(searchFor);
    }

    if (foundPos !== -1) {
        textarea.selectionStart = foundPos;
        textarea.selectionEnd = foundPos + findText.length;
        $textarea.focus();

        // Scroll al texto encontrado
        var lineHeight = parseInt($textarea.css('line-height')) || 20;
        var linesAbove = text.substring(0, foundPos).split('\n').length - 1;
        textarea.scrollTop = linesAbove * lineHeight - 100;
    } else {
        alert('No se encontro: ' + findText);
    }
}

// Reemplazar todo
function editorReplaceAll(targetId) {
    var $textarea = $('#' + targetId);
    var findText = $('#find-text-' + targetId).val();
    var replaceText = $('#replace-text-' + targetId).val();
    var caseSensitive = $('#case-sensitive-' + targetId).is(':checked');

    if (!findText) return;

    var text = $textarea.val();
    var flags = caseSensitive ? 'g' : 'gi';
    var regex = new RegExp(escapeRegExp(findText), flags);
    var count = (text.match(regex) || []).length;

    if (count === 0) {
        alert('No se encontro: ' + findText);
        return;
    }

    if (confirm('Se reemplazaran ' + count + ' ocurrencias. ¿Continuar?')) {
        var newText = text.replace(regex, replaceText);
        $textarea.val(newText);
        updateEditorStats(targetId);
        saveToHistory(targetId);
        $('#modalBuscarReemplazar-' + targetId).modal('hide');
    }
}

// Escapar caracteres especiales para regex
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Auto-inicializar editores cuando el DOM este listo
$(document).ready(function() {
    $('.editor-toolbar').each(function() {
        var targetId = $(this).data('target').replace('#', '');
        initEditor(targetId);
    });
});
</script>
