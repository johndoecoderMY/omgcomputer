<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('customers.index');
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(Customer::query()->with('project')->withCount('repairTickets'))
            ->filter(function ($customers) use ($request): void {
                $search = trim((string) $request->input('search.value', ''));
                if ($search !== '') {
                    $customers->where(fn ($matches) => $matches
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%"));
                }
            }, true)
            ->addColumn('customer', fn (Customer $customer): string => sprintf(
                '<a class="fw-semibold" href="%s">%s</a>%s',
                e(route('customers.show', $customer)),
                e($customer->name),
                $customer->company ? '<div class="small text-body-secondary">'.e($customer->company).'</div>' : '',
            ))
            ->addColumn('contact', fn (Customer $customer): string => e($customer->email ?: '—')
                .'<div class="small text-body-secondary">'.e($customer->phone ?: '').'</div>')
            ->addColumn('project_name', fn (Customer $customer): string => $customer->project
                ? sprintf('<a href="%s">%s</a>', e(route('projects.show', $customer->project)), e($customer->project->name))
                : '<span class="text-body-secondary">—</span>')
            ->addColumn('actions', fn (Customer $customer): string => sprintf(
                '<a class="btn btn-sm btn-outline-secondary" href="%s">View profile</a>',
                e(route('customers.show', $customer)),
            ))
            ->rawColumns(['customer', 'contact', 'project_name', 'actions'])
            ->toJson();
    }

    public function create(Request $request): View
    {
        return view('customers.create', [
            'customer' => new Customer(['project_id' => $request->integer('project_id') ?: null]),
            'projects' => Project::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create($this->validatedData($request));

        return redirect()->route('customers.show', $customer)->with('success', 'Customer profile created.');
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'project',
            'repairTickets' => fn ($query) => $query->with(['project', 'assignees'])->latest(),
        ]);

        $account = $customer->email
            ? User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($customer->email))])
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'customer'))
                ->first()
            : null;

        return view('customers.show', compact('customer', 'account'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', [
            'customer' => $customer,
            'projects' => Project::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validatedData($request, $customer));

        return redirect()->route('customers.show', $customer)->with('success', 'Customer profile updated.');
    }

    private function validatedData(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer?->id)],
            'phone' => ['nullable', 'string', 'max:64'],
            'company' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'address' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
