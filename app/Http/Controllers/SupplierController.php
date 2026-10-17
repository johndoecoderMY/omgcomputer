<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function index(): View
    {
        return view('inventory.suppliers');
    }

    public function datatable()
    {
        return DataTables::eloquent(Supplier::query()->withCount('parts'))
            ->addColumn('actions', fn (Supplier $supplier): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">Edit</a>',
                e(route('inventory.suppliers.edit', $supplier)),
            ))
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function edit(Supplier $supplier): View
    {
        return view('inventory.supplier-edit', compact('supplier'));
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::query()->create($this->validatedData($request));

        return redirect()->route('inventory.suppliers.index')->with('success', 'Supplier added.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validatedData($request, $supplier));

        return redirect()->route('inventory.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('inventory.suppliers.index')
            ->with('success', 'Supplier deleted. Linked parts remain in inventory without a supplier.');
    }

    private function validatedData(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($supplier?->id)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
