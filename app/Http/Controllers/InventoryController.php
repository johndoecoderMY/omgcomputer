<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\Supplier;
use App\Services\InventoryStock;
use App\Support\DataTableQueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Yajra\DataTables\Facades\DataTables;

class InventoryController extends Controller
{
    public function index(): View
    {
        return view('inventory.overview', [
            'partCount' => InventoryPart::count(),
            'lowStockCount' => InventoryPart::query()->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'supplierCount' => Supplier::count(),
            'recentMovements' => InventoryMovement::query()->with('part')->latest()->limit(8)->get(),
        ]);
    }

    public function parts(Request $request): View
    {
        return view('inventory.index', [
            'partCount' => InventoryPart::count(),
            'lowStockCount' => InventoryPart::query()->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
        ]);
    }

    public function create(): View
    {
        return view('inventory.create', [
            'part' => new InventoryPart(['type' => 'other', 'stock_quantity' => 0, 'reorder_level' => 0, 'cost_price' => 0, 'selling_price' => 0]),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'types' => InventoryPart::TYPES,
        ]);
    }

    public function show(InventoryPart $part): View
    {
        $part->load('supplier');

        return view('inventory.show', compact('part'));
    }

    public function edit(InventoryPart $part): View
    {
        return view('inventory.edit', [
            'part' => $part,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'types' => InventoryPart::TYPES,
        ]);
    }

    public function update(Request $request, InventoryPart $part): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('inventory_parts', 'sku')->ignore($part->id)],
            'type' => ['nullable', Rule::in(array_keys(InventoryPart::TYPES))],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);
        $data['type'] ??= $part->type;
        $part->update($data);

        return redirect()->route('inventory.parts.show', $part)->with('success', 'Inventory item updated.');
    }

    public function delete(InventoryPart $part): View
    {
        return view('inventory.delete', compact('part'));
    }

    public function destroy(InventoryPart $part): RedirectResponse
    {
        $part->delete();

        return redirect()->route('inventory.parts')->with('success', 'Inventory item archived. Its stock movement history is preserved.');
    }

    public function partsDatatable(Request $request)
    {
        $query = InventoryPart::query()
            ->leftJoin('suppliers', 'inventory_parts.supplier_id', '=', 'suppliers.id')
            ->select('inventory_parts.*', 'suppliers.name as supplier_name');

        return DataTables::eloquent(DataTableQueryBuilder::for($query, $request, [
            'name' => AllowedFilter::partial('name', 'inventory_parts.name'),
            'sku' => AllowedFilter::partial('sku', 'inventory_parts.sku'),
            'type' => AllowedFilter::exact('type', 'inventory_parts.type'),
            'supplier' => AllowedFilter::partial('supplier', 'suppliers.name'),
            'low_stock' => AllowedFilter::callback('low_stock', function ($query, string $value): void {
                if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                    $query->whereColumn('inventory_parts.stock_quantity', '<=', 'inventory_parts.reorder_level');
                }
            }),
        ]))
            ->addColumn('type_label', fn (InventoryPart $part): string => InventoryPart::TYPES[$part->type] ?? $part->type)
            ->editColumn('stock_quantity', fn (InventoryPart $part): string => sprintf(
                '<span class="badge text-bg-%s">%s</span>',
                $part->stock_quantity <= $part->reorder_level ? 'danger' : 'success',
                $part->stock_quantity,
            ))
            ->editColumn('cost_price', fn (InventoryPart $part): string => 'RM '.number_format((float) $part->cost_price, 2))
            ->editColumn('selling_price', fn (InventoryPart $part): string => 'RM '.number_format((float) $part->selling_price, 2))
            ->addColumn('actions', fn (InventoryPart $part): string => sprintf(
                '<div class="d-flex gap-1"><a class="btn btn-sm btn-outline-secondary" href="%s">Details</a><a class="btn btn-sm btn-outline-primary" href="%s">Edit</a><form class="d-flex gap-1" method="POST" action="%s"><input type="hidden" name="_token" value="%s"><select class="form-select form-select-sm" name="direction" aria-label="Stock adjustment direction"><option value="receive">Receive</option><option value="use">Use</option></select><input class="form-control form-control-sm" name="quantity" type="number" min="1" placeholder="Qty" required style="max-width:85px"><button class="btn btn-sm btn-outline-primary" aria-label="Save stock adjustment"><span class="bi bi-check"></span></button></form></div>',
                e(route('inventory.parts.show', $part)),
                e(route('inventory.parts.edit', $part)),
                e(route('inventory.movements.store', $part)),
                e(csrf_token()),
            ))
            ->rawColumns(['stock_quantity', 'actions'])
            ->toJson();
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
            'type' => ['nullable', Rule::in(array_keys(InventoryPart::TYPES))],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:10000000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);
        $data['type'] ??= 'other';

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
        return view('inventory.movements');
    }

    public function movementsDatatable(Request $request)
    {
        return DataTables::eloquent(DataTableQueryBuilder::for(
            InventoryMovement::query()
                ->leftJoin('inventory_parts', 'inventory_movements.inventory_part_id', '=', 'inventory_parts.id')
                ->leftJoin('users', 'inventory_movements.user_id', '=', 'users.id')
                ->select('inventory_movements.*', 'inventory_parts.name as part_name', 'users.name as user_name'),
            $request,
            [
                'type' => AllowedFilter::exact('type', 'inventory_movements.type'),
                'part_name' => AllowedFilter::partial('part_name', 'inventory_parts.name'),
                'user_name' => AllowedFilter::partial('user_name', 'users.name'),
            ],
        ))
            ->editColumn('created_at', fn (InventoryMovement $movement): string => $movement->created_at->format('M j, Y g:i A'))
            ->editColumn('part_name', fn (InventoryMovement $movement): string => $movement->part_name ?? 'Deleted item')
            ->editColumn('quantity_change', fn (InventoryMovement $movement): string => $movement->quantity_change > 0
                ? '+'.$movement->quantity_change
                : (string) $movement->quantity_change)
            ->editColumn('user_name', fn (InventoryMovement $movement): string => $movement->user_name ?? 'System')
            ->toJson();
    }
}
