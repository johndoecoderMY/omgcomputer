<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Services\InventoryStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        return view('inventory.overview', [
            'partCount' => InventoryPart::count(),
            'lowStockCount' => InventoryPart::query()->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'recentMovements' => InventoryMovement::query()->with('part')->latest()->limit(8)->get(),
        ]);
    }

    public function parts(Request $request): View
    {
        $query = InventoryPart::query()->orderBy('name');
        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'reorder_level');
        }

        $parts = $query->paginate(25)->withQueryString();

        return view('inventory.index', [
            'parts' => $parts,
            'partCount' => InventoryPart::count(),
            'lowStockCount' => InventoryPart::query()->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'stock_quantity' => $request->input('stock_quantity', $request->input('opening_stock')),
            'cost_price' => $request->input('cost_price', $request->input('unit_cost')),
            'selling_price' => $request->input('selling_price', $request->input('sale_price')),
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:inventory_parts,sku'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:10000000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $part = InventoryPart::create($data);
            if ($part->stock_quantity > 0) {
                $part->movements()->create([
                    'user_id' => $request->user()->id,
                    'type' => 'received',
                    'quantity_change' => $part->stock_quantity,
                    'quantity_after' => $part->stock_quantity,
                    'note' => 'Opening stock',
                ]);
            }
        });

        return redirect()->route('inventory.parts')->with('success', 'Inventory item added.');
    }

    public function adjust(InventoryPart $part, Request $request, InventoryStock $stock): RedirectResponse
    {
        if ($request->filled('type')) {
            $request->merge(['direction' => $request->input('type') === 'used' ? 'use' : 'receive']);
        }
        $data = $request->validate([
            'direction' => ['required', 'in:receive,use'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['direction'] === 'receive') {
            $stock->receive($part, $request->user(), $data['quantity'], $data['note'] ?? 'Manual stock receipt');
        } else {
            $stock->use($part, $request->user(), $data['quantity'], $data['note'] ?? 'Manual stock adjustment');
        }

        return redirect()->route('inventory.movements')->with('success', 'Stock quantity updated.');
    }

    public function movements(): View
    {
        return view('inventory.movements', [
            'movements' => InventoryMovement::query()->with(['part', 'user'])->latest()->paginate(30),
        ]);
    }
}