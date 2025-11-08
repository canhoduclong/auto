<?php

namespace App\Http\Controllers;

use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryDocumentController extends Controller
{
    public function index()
    {
        $inventoryDocuments = InventoryDocument::with('warehouse', 'user')->latest()->paginate(10);
        return view('inventory-documents.index', compact('inventoryDocuments'));
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        $productVariants = ProductVariant::with('product')->get();
        return view('inventory-documents.create', compact('warehouses', 'productVariants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_date' => 'required|date',
            'type' => 'required|string|in:import,export,adjustment',
            'warehouse_id' => 'required|exists:warehouses,id',
            'shipping_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $document = InventoryDocument::create([
                'document_date' => $validated['document_date'],
                'type' => $validated['type'],
                'warehouse_id' => $validated['warehouse_id'],
                'shipping_fee' => $validated['shipping_fee'] ?? 0,
                'notes' => $validated['notes'],
                'user_id' => Auth::id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $document->items()->create($itemData);

                $inventory = Inventory::firstOrCreate(
                    [
                        'product_variant_id' => $itemData['product_variant_id'],
                        'warehouse_id' => $validated['warehouse_id'],
                    ],
                    ['quantity' => 0]
                );

                $quantityChange = $itemData['quantity'];
                if ($validated['type'] == 'export') {
                    $quantityChange = -$quantityChange;
                }

                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'quantity' => $quantityChange,
                    'type' => $validated['type'],
                    'reference_id' => $document->id,
                    'reference_type' => InventoryDocument::class,
                    'user_id' => Auth::id(),
                ]);

                $inventory->quantity += $quantityChange;
                $inventory->save();
            }
        });

        return redirect()->route('inventory-documents.index')->with('success', 'Inventory document created successfully.');
    }

    public function show(InventoryDocument $inventoryDocument)
    {
        $inventoryDocument->load('items.productVariant.product', 'warehouse', 'user');
        return view('inventory-documents.show', compact('inventoryDocument'));
    }

    public function edit(InventoryDocument $inventoryDocument)
    {
        $warehouses = Warehouse::all();
        $productVariants = ProductVariant::with('product')->get();
        $inventoryDocument->load('items');
        return view('inventory-documents.edit', compact('inventoryDocument', 'warehouses', 'productVariants'));
    }

    public function update(Request $request, InventoryDocument $inventoryDocument)
    {
        // For simplicity, we will delete and recreate the items and movements.
        // A more robust solution would compare and update existing items.
        DB::transaction(function () use ($request, $inventoryDocument) {
            // 1. Revert old movements
            foreach ($inventoryDocument->items as $item) {
                $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                                    ->where('warehouse_id', $inventoryDocument->warehouse_id)
                                    ->first();
                if ($inventory) {
                    $quantityChange = $item->quantity;
                    if ($inventoryDocument->type == 'export') {
                        $quantityChange = -$quantityChange;
                    }
                    $inventory->quantity -= $quantityChange;
                    $inventory->save();
                }
            }
            InventoryMovement::where('reference_id', $inventoryDocument->id)
                             ->where('reference_type', InventoryDocument::class)
                             ->delete();
            $inventoryDocument->items()->delete();

            // 2. Store new data
            $validated = $request->validate([
                'document_date' => 'required|date',
                'type' => 'required|string|in:import,export,adjustment',
                'warehouse_id' => 'required|exists:warehouses,id',
                'shipping_fee' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_variant_id' => 'required|exists:product_variants,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_cost' => 'required|numeric|min:0',
            ]);

            $inventoryDocument->update([
                'document_date' => $validated['document_date'],
                'type' => $validated['type'],
                'warehouse_id' => $validated['warehouse_id'],
                'shipping_fee' => $validated['shipping_fee'] ?? 0,
                'notes' => $validated['notes'],
            ]);

            foreach ($validated['items'] as $itemData) {
                $inventoryDocument->items()->create($itemData);

                $inventory = Inventory::firstOrCreate(
                    [
                        'product_variant_id' => $itemData['product_variant_id'],
                        'warehouse_id' => $validated['warehouse_id'],
                    ],
                    ['quantity' => 0]
                );

                $quantityChange = $itemData['quantity'];
                if ($validated['type'] == 'export') {
                    $quantityChange = -$quantityChange;
                }

                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'quantity' => $quantityChange,
                    'type' => $validated['type'],
                    'reference_id' => $inventoryDocument->id,
                    'reference_type' => InventoryDocument::class,
                    'user_id' => Auth::id(),
                ]);

                $inventory->quantity += $quantityChange;
                $inventory->save();
            }
        });

        return redirect()->route('inventory-documents.index')->with('success', 'Inventory document updated successfully.');
    }

    public function destroy(InventoryDocument $inventoryDocument)
    {
        DB::transaction(function () use ($inventoryDocument) {
            // Revert movements before deleting
            foreach ($inventoryDocument->items as $item) {
                $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                                    ->where('warehouse_id', $inventoryDocument->warehouse_id)
                                    ->first();
                if ($inventory) {
                    $quantityChange = $item->quantity;
                    if ($inventoryDocument->type == 'export') {
                        $quantityChange = -$quantityChange;
                    }
                    $inventory->quantity -= $quantityChange;
                    $inventory->save();
                }
            }
            $inventoryDocument->delete(); // Items and movements will be deleted by cascade or manually
        });

        return redirect()->route('inventory-documents.index')->with('success', 'Inventory document deleted successfully.');
    }
}