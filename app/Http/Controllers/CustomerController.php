<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::query()->with('project')->withCount('repairTickets');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($customers) => $customers
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%"));
        }

        return view('customers.index', [
            'customers' => $query->orderBy('name')->paginate(25)->withQueryString(),
        ]);
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
            'repairTickets' => fn ($query) => $query->with(['project', 'assignee'])->latest(),
        ]);

        return view('customers.show', compact('customer'));
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