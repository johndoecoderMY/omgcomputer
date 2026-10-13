<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RepairTicket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $customers = Customer::query()
            ->withCount('tickets')
            ->withMax('tickets', 'created_at')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderByDesc('tickets_max_created_at')
            ->orderBy('name')
            ->get();

        return view('customers.index', compact('customers', 'search'));
    }

    public function show(Customer $customer): View
    {
        $customer->load(['tickets.creator:id,name', 'tickets.assignee:id,name']);

        return view('customers.show', [
            'customer' => $customer,
            'statuses' => RepairTicket::STATUSES,
        ]);
    }
}
