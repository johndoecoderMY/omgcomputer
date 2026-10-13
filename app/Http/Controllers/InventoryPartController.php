<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Services\InventoryStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryPartController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $lowOnly = $request->boolean('low_stock');
        $partsQuery = InventoryPart::query()->where('is_active', true);

        if ($search !== '') {
            $partsQuery->where(function ($query) use ($search): void {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($lowOnly) {
            $partsQuery->whereColumn('stock_quantity', '<=', 'reorder_level');
        }

        $parts = $partsQuery->orderBy('name')->get();
        $movements = InventoryMovement::query()
            ->with(['part:id,sku,name', 'user:id,name'])
            ->whereHas('part', fn ($query) => $query->where('is_active', true))
            ->latest()
            ->limit(40)
            ->get();

        return view('inventory.index', [
            'parts' => $parts,
            'movements' => $movements,
            'search' => $search,
            'lowOnly' => $lowOnly,
            'partCount' => InventoryPart::where('is_active', true)->count(),
            'lowStockCount' => InventoryPart::where('is_active', true)
                ->whereColumn('stock_quantity', '<=', 'reorder_level')
                ->count(),
            'stockValue' => (float) InventoryPart::where('is_active', true)
                ->selectRaw('COALESCE(SUM(stock_quantity * unit_cost), 0) as value')
                ->value('value'),
        ]);
    }

    public function store(Request $request, InventoryStock $stock): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:80', 'unique:inventory_parts,sku'],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'opening_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        DB::transaction(function () use ($request, $stock, $validated): void {
            $part = InventoryPart::create([
                'user_id' => $request->user()->id,
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'stock_quantity' => 0,
                'reorder_level' => $validated['reorder_level'],
                'unit_cost' => $validated['unit_cost'],
                'sale_price' => $validated['sale_price'],
                'is_active' => true,
            ]);

            if ($validated['opening_stock'] > 0) {
                $stock->receive($part, $request->user(), (int) $validated['opening_stock'], 'Opening stock');
            }
        });

        return redirect()->route('inventory.index')->with('status', 'Part added to inventory.');
    }

    public function update(Request $request, InventoryPart $inventoryPart): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:80', Rule::unique('inventory_parts', 'sku')->ignore($inventoryPart->id)],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $inventoryPart->update($validated);

        return redirect()->route('inventory.index')->with('status', 'Part details updated.');
    }

    public function moveStock(Request $request, InventoryPart $inventoryPart, InventoryStock $stock): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['received', 'used', 'counted'])],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['type'] !== 'counted' && $validated['quantity'] < 1) {
            return back()->withErrors(['quantity' => 'Quantity must be at least one.'])->withInput();
        }

        if ($validated['type'] === 'received') {
            $stock->receive($inventoryPart, $request->user(), (int) $validated['quantity'], $validated['note'] ?? null);
        } elseif ($validated['type'] === 'used') {
            $stock->use($inventoryPart, $request->user(), (int) $validated['quantity'], $validated['note'] ?? null);
        } else {
            $stock->setCount($inventoryPart, $request->user(), (int) $validated['quantity'], $validated['note'] ?? null);
        }

        return redirect()->route('inventory.index')->with('status', 'Stock movement recorded.');
    }
}
