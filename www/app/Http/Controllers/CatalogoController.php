<?php

namespace App\Http\Controllers;

use App\Models\CatCat;
use App\Models\CatItem;
use App\Models\TrazaActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatalogoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Listado de catalogos
     */
    public function index()
    {
        $catalogos = CatCat::withCount('rel_items')
            ->orderBy('nombre')
            ->paginate(20);

        return view('catalogos.index', compact('catalogos'));
    }

    /**
     * Ver items de un catalogo
     */
    public function show($id)
    {
        $catalogo = CatCat::findOrFail($id);
        $items = CatItem::where('id_cat', $id)
            ->orderBy('orden')
            ->orderBy('descripcion')
            ->paginate(50);

        return view('catalogos.show', compact('catalogo', 'items'));
    }

    /**
     * Formulario para crear nuevo catalogo
     */
    public function create()
    {
        return view('catalogos.create');
    }

    /**
     * Guardar nuevo catalogo
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:catalogos.cat_cat,nombre',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $catalogo = CatCat::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'editable' => 1,
        ]);

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => Auth::id(),
            'accion' => 'crear',
            'objeto' => 'catalogo',
            'id_registro' => $catalogo->id_cat,
            'referencia' => $request->nombre,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('catalogos.show', $catalogo->id_cat)
            ->with('success', 'Catalogo creado correctamente.');
    }

    /**
     * Formulario para editar catalogo
     */
    public function edit($id)
    {
        $catalogo = CatCat::findOrFail($id);
        return view('catalogos.edit', compact('catalogo'));
    }

    /**
     * Actualizar catalogo
     */
    public function update(Request $request, $id)
    {
        $catalogo = CatCat::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100|unique:catalogos.cat_cat,nombre,' . $id . ',id_cat',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $catalogo->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => Auth::id(),
            'accion' => 'editar',
            'objeto' => 'catalogo',
            'id_registro' => $catalogo->id_cat,
            'referencia' => $request->nombre,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('catalogos.show', $catalogo->id_cat)
            ->with('success', 'Catalogo actualizado correctamente.');
    }

    /**
     * Formulario para crear nuevo item
     */
    public function createItem($id_cat)
    {
        $catalogo = CatCat::findOrFail($id_cat);
        $maxOrden = CatItem::where('id_cat', $id_cat)->max('orden') ?? 0;

        return view('catalogos.items.create', compact('catalogo', 'maxOrden'));
    }

    /**
     * Guardar nuevo item
     */
    public function storeItem(Request $request, $id_cat)
    {
        $catalogo = CatCat::findOrFail($id_cat);

        $request->validate([
            'descripcion' => 'required|string|max:255',
            'abreviado' => 'nullable|string|max:50',
            'orden' => 'required|integer|min:0',
        ]);

        $item = CatItem::create([
            'id_cat' => $id_cat,
            'descripcion' => $request->descripcion,
            'abreviado' => $request->abreviado,
            'orden' => $request->orden,
            'habilitado' => 1,
            'predeterminado' => $request->has('predeterminado') ? 1 : 0,
        ]);

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => Auth::id(),
            'accion' => 'crear',
            'objeto' => 'item_catalogo',
            'id_registro' => $item->id_item,
            'referencia' => $catalogo->nombre . ' - ' . $request->descripcion,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('catalogos.show', $id_cat)
            ->with('success', 'Item agregado correctamente.');
    }

    /**
     * Formulario para editar item
     */
    public function editItem($id_cat, $id_item)
    {
        $catalogo = CatCat::findOrFail($id_cat);
        $item = CatItem::where('id_cat', $id_cat)
            ->where('id_item', $id_item)
            ->firstOrFail();

        return view('catalogos.items.edit', compact('catalogo', 'item'));
    }

    /**
     * Actualizar item
     */
    public function updateItem(Request $request, $id_cat, $id_item)
    {
        $catalogo = CatCat::findOrFail($id_cat);
        $item = CatItem::where('id_cat', $id_cat)
            ->where('id_item', $id_item)
            ->firstOrFail();

        $request->validate([
            'descripcion' => 'required|string|max:255',
            'abreviado' => 'nullable|string|max:50',
            'orden' => 'required|integer|min:0',
        ]);

        $item->update([
            'descripcion' => $request->descripcion,
            'abreviado' => $request->abreviado,
            'orden' => $request->orden,
            'habilitado' => $request->has('habilitado') ? 1 : 0,
            'predeterminado' => $request->has('predeterminado') ? 1 : 0,
        ]);

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => Auth::id(),
            'accion' => 'editar',
            'objeto' => 'item_catalogo',
            'id_registro' => $item->id_item,
            'referencia' => $catalogo->nombre . ' - ' . $request->descripcion,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('catalogos.show', $id_cat)
            ->with('success', 'Item actualizado correctamente.');
    }

    /**
     * Cambiar estado habilitado de un item
     */
    public function toggleItem($id_cat, $id_item)
    {
        $catalogo = CatCat::findOrFail($id_cat);
        $item = CatItem::where('id_cat', $id_cat)
            ->where('id_item', $id_item)
            ->firstOrFail();

        $item->habilitado = $item->habilitado ? 0 : 1;
        $item->save();

        $accion = $item->habilitado ? 'habilitado' : 'deshabilitado';

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => Auth::id(),
            'accion' => 'editar',
            'objeto' => 'item_catalogo',
            'id_registro' => $item->id_item,
            'referencia' => "Item $accion: " . $item->descripcion,
            'ip' => request()->ip(),
        ]);

        return redirect()->route('catalogos.show', $id_cat)
            ->with('success', "Item $accion correctamente.");
    }

    /**
     * Reordenar items (AJAX)
     */
    public function reorderItems(Request $request, $id_cat)
    {
        try {
            $catalogo = CatCat::findOrFail($id_cat);

            $items = $request->input('items', []);

            if (empty($items)) {
                return response()->json(['success' => false, 'message' => 'No se recibieron items'], 400);
            }

            foreach ($items as $itemData) {
                if (isset($itemData['id']) && isset($itemData['orden'])) {
                    CatItem::where('id_item', $itemData['id'])
                        ->where('id_cat', $id_cat)
                        ->update(['orden' => (int) $itemData['orden']]);
                }
            }

            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => Auth::id(),
                'accion' => 'reordenar',
                'objeto' => 'item_catalogo',
                'id_registro' => $id_cat,
                'referencia' => 'Reordenamiento de items del catalogo: ' . $catalogo->nombre,
                'ip' => $request->ip(),
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
