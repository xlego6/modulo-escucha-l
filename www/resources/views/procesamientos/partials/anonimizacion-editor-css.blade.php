{{-- Estilos del editor de anonimizacion (compartido: editar-anonimizacion-asignada / revisar-anonimizacion) --}}
<style>
    .entity-original {
        padding: 2px 6px;
        border-radius: 4px;
        margin: 0 2px;
        cursor: context-menu;
    }
    .entity-original-label {
        font-size: 9px;
        font-weight: bold;
        vertical-align: super;
        margin-left: 2px;
        opacity: 0.8;
        user-select: none;
    }
    .entity-NUMERO { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    .entity-PERSONA { background-color: #cce5ff; border: 1px solid #b8daff; color: #004085; }
    .entity-ORGANIZACION { background-color: #e2d9f3; border: 1px solid #d4c5ec; color: #4a2a7a; }
    .entity-LUGAR { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
    .entity-GENTILICIO { background-color: #dcdcf7; border: 1px solid #c7c7f0; color: #34348a; }
    .entity-FECHA { background-color: #e2e3e5; border: 1px solid #d6d8db; color: #383d41; }
    .entity-EDAD { background-color: #ffe5d0; border: 1px solid #ffd8b8; color: #7a4a12; }
    .entity-OCUPACION { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
    .entity-GRUPO_ARMADO { background-color: #e8c4c4; border: 1px solid #dba8a8; color: #5c1f1f; }
    .entity-ROL_ARMADO { background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; }
    .entity-ETNICO { background-color: #c8f0d4; border: 1px solid #a8e6bc; color: #1e5c34; }
    /* Estilos para editor visual de entidades */
    .entity-clickable {
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }
    .entity-clickable:hover {
        opacity: 0.8;
        transform: scale(1.02);
    }
    .entity-hover-sync {
        box-shadow: 0 0 0 2px #000;
    }
    /* Ultima etiqueta tocada antes de guardar (se resalta al recargar) */
    .entity-ultima {
        box-shadow: 0 0 0 3px #ffc107;
    }
    .entity-cubierta {
        background-color: #343a40;
        color: #fff;
        padding: 2px 6px;
        border-radius: 4px;
        margin: 0 2px;
        display: inline;
    }
    .entity-descubierta {
        padding: 2px 6px;
        border-radius: 4px;
        margin: 0 2px;
        display: inline;
    }
    .editor-visual-container {
        position: relative;
        line-height: 2.2;
        font-size: 14px;
        min-height: 400px;
        max-height: 600px;
        overflow-y: auto;
        padding: 15px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        white-space: pre-wrap;
    }
    .leyenda-entidades {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 10px;
    }
    .leyenda-item {
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 3px 8px;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        background: #f8f9fa;
    }
    /* Menus contextuales */
    .entity-menu {
        position: fixed;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 8px 0;
        z-index: 1050;
        min-width: 160px;
        display: none;
    }
    .entity-menu.show {
        display: block;
    }
    .entity-menu-header {
        padding: 4px 12px;
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        border-bottom: 1px solid #eee;
        margin-bottom: 4px;
    }
    .entity-menu-item {
        padding: 6px 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }
    .entity-menu-item:hover {
        background: #f8f9fa;
    }
    .entity-menu-item .badge {
        min-width: 45px;
        text-align: center;
    }
    .texto-seleccionable {
        cursor: text;
    }
    .texto-seleccionable::selection {
        background: #ffc107;
        color: #000;
    }
    /* Estado activo de los botones de cobertura (Todas/Ninguna) */
    .btn-cobertura.active {
        background-color: #343a40;
        border-color: #343a40;
        color: #fff;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.25);
    }
    /* Tarjeta "Etiquetas asignadas" */
    .etiqueta-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 5px 8px;
        border-bottom: 1px solid #f1f1f1;
        border-left: 3px solid transparent;
        font-size: 12px;
        transition: background-color 0.1s ease;
    }
    .etiqueta-item:last-child { border-bottom: none; }
    .etiqueta-item:hover {
        background: #fff8e1;
        border-left-color: #ffc107;
    }
    .etiqueta-item .etiqueta-acciones {
        white-space: nowrap;
        opacity: 0.45;
    }
    .etiqueta-item:hover .etiqueta-acciones { opacity: 1; }
    .etiqueta-item .etiqueta-acciones .btn {
        padding: 0 4px;
        line-height: 1;
    }
    .etiqueta-variante {
        display: inline-block;
        margin-right: 6px;
    }
    .etiqueta-variante .btn-separar-variante {
        padding: 0 2px;
        font-size: 10px;
        line-height: 1;
        vertical-align: baseline;
    }
</style>
