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
        $this->assertWarehouseAssignment();

        $query = InventoryDocument::with('warehouse', 'user')->latest();

        $managedWarehouseId = $this->getManagedWarehouseId();
        if ($managedWarehouseId) {
            $query->where('warehouse_id', $managedWarehouseId);
        }

        $inventoryDocuments = $query->paginate(10);
        return view('inventory-documents.index', compact('inventoryDocuments'));
    }

    public function create()
    {
        $this->assertWarehouseAssignment();

        $warehouses = $this->getAllowedWarehouses();
        $productVariants = ProductVariant::with('product')->get();
        return view('inventory-documents.create', compact('warehouses', 'productVariants'));
    }

    public function store(Request $request)
    {
        $this->assertWarehouseAssignment();

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

        DB::transaction(function () use ($validated) {
            $warehouseId = $this->resolveWarehouseId($validated['warehouse_id']);

            $document = InventoryDocument::create([
                'document_date' => $validated['document_date'],
                'type' => $validated['type'],
                'warehouse_id' => $warehouseId,
                'shipping_fee' => $validated['shipping_fee'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'user_id' => Auth::id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $document->items()->create($itemData);

                $inventory = Inventory::firstOrCreate(
                    [
                        'product_variant_id' => $itemData['product_variant_id'],
                        'warehouse_id' => $warehouseId,
                    ],
                    ['quantity' => 0]
                );

                $quantityChange = $itemData['quantity'];
                if ($validated['type'] == 'export') {
                    $availableQty = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
                    if ($availableQty < $itemData['quantity']) {
                        throw new \RuntimeException(__('inventory.messages.insufficient_stock_for_export'));
                    }
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

                $this->syncVariantStockFromInventories((int) $itemData['product_variant_id']);
            }
        });

        return redirect()->route('inventory-documents.index')->with('success', __('inventory.messages.created'));
    }

    public function show(InventoryDocument $inventoryDocument)
    {
        $inventoryDocument->load('items.productVariant.product', 'warehouse', 'user');
        return view('inventory-documents.show', compact('inventoryDocument'));
    }

    public function edit(InventoryDocument $inventoryDocument)
    {
        $this->assertWarehouseAssignment();

        $managedWarehouseId = $this->getManagedWarehouseId();
        if ($managedWarehouseId && (int) $inventoryDocument->warehouse_id !== $managedWarehouseId) {
            abort(403, __('inventory.messages.forbidden_edit_other_warehouse'));
        }

        $warehouses = $this->getAllowedWarehouses();
        $productVariants = ProductVariant::with('product')->get();
        $inventoryDocument->load('items');
        return view('inventory-documents.edit', compact('inventoryDocument', 'warehouses', 'productVariants'));
    }

    public function update(Request $request, InventoryDocument $inventoryDocument)
    {
        $this->assertWarehouseAssignment();

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

                    $this->syncVariantStockFromInventories((int) $item->product_variant_id);
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

            $warehouseId = $this->resolveWarehouseId($validated['warehouse_id']);

            $inventoryDocument->update([
                'document_date' => $validated['document_date'],
                'type' => $validated['type'],
                'warehouse_id' => $warehouseId,
                'shipping_fee' => $validated['shipping_fee'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $inventoryDocument->items()->create($itemData);

                $inventory = Inventory::firstOrCreate(
                    [
                        'product_variant_id' => $itemData['product_variant_id'],
                        'warehouse_id' => $warehouseId,
                    ],
                    ['quantity' => 0]
                );

                $quantityChange = $itemData['quantity'];
                if ($validated['type'] == 'export') {
                    $availableQty = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
                    if ($availableQty < $itemData['quantity']) {
                        throw new \RuntimeException(__('inventory.messages.insufficient_stock_for_export'));
                    }
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

                $this->syncVariantStockFromInventories((int) $itemData['product_variant_id']);
            }
        });

        return redirect()->route('inventory-documents.index')->with('success', __('inventory.messages.updated'));
    }

    public function destroy(InventoryDocument $inventoryDocument)
    {
        $this->assertWarehouseAssignment();

        $managedWarehouseId = $this->getManagedWarehouseId();
        if ($managedWarehouseId && (int) $inventoryDocument->warehouse_id !== $managedWarehouseId) {
            abort(403, __('inventory.messages.forbidden_delete_other_warehouse'));
        }

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

                    $this->syncVariantStockFromInventories((int) $item->product_variant_id);
                }
            }
            $inventoryDocument->delete(); // Items and movements will be deleted by cascade or manually
        });

        return redirect()->route('inventory-documents.index')->with('success', __('inventory.messages.deleted'));
    }

    private function getManagedWarehouseId(): ?int
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('warehouse')) {
            return null;
        }

        return $user->warehouse_id ? (int) $user->warehouse_id : null;
    }

    private function assertWarehouseAssignment(): void
    {
        $user = Auth::user();
        if ($user && $user->hasRole('warehouse') && !$user->warehouse_id) {
            abort(403, __('inventory.messages.warehouse_unassigned'));
        }
    }

    private function getAllowedWarehouses()
    {
        $managedWarehouseId = $this->getManagedWarehouseId();
        if ($managedWarehouseId) {
            return Warehouse::where('id', $managedWarehouseId)->get();
        }

        return Warehouse::all();
    }

    private function resolveWarehouseId(int $requestedWarehouseId): int
    {
        $managedWarehouseId = $this->getManagedWarehouseId();
        if ($managedWarehouseId) {
            return $managedWarehouseId;
        }

        return $requestedWarehouseId;
    }

    private function syncVariantStockFromInventories(int $variantId): void
    {
        $totalStock = (int) Inventory::where('product_variant_id', $variantId)->sum('quantity');
        ProductVariant::where('id', $variantId)->update(['stock' => $totalStock]);
    }
}