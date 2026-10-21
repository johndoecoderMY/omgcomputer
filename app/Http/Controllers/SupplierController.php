<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Support\DataTableQueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function index(): View
    {
        return view('inventory.suppliers');
    }

    public function create(): View
    {
        return view('suppliers.create', ['supplier' => new Supplier]);
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(DataTableQueryBuilder::for(Supplier::query()->withCount('parts'), $request, [
            'name' => AllowedFilter::partial('name'),
            'email' => AllowedFilter::partial('email'),
            'phone' => AllowedFilter::partial('phone'),
            'website' => AllowedFilter::partial('website'),
        ]))
            ->addColumn('website_link', fn (Supplier $supplier): string => $supplier->website
                ? sprintf(
                    '<a href="%s" target="_blank" rel="noopener noreferrer">Visit website</a>',
                    e($supplier->website),
                )
                : '—')
            ->addColumn('actions', fn (Supplier $supplier): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">View</a>',
                e(route('inventory.suppliers.show', $supplier)),
            ))
            ->rawColumns(['website_link', 'actions'])
            ->toJson();
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load('parts');

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::query()->create($this->validatedData($request));

        return redirect()->route('inventory.suppliers.show', $supplier)->with('success', 'Supplier added.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validatedData($request, $supplier));

        return redirect()->route('inventory.suppliers.show', $supplier)->with('success', 'Supplier updated.');
    }

    public function delete(Supplier $supplier): View
    {
        $supplier->loadCount('parts');

        return view('suppliers.delete', compact('supplier'));
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
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
