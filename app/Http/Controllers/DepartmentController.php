<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Support\DataTableQueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('teams.departments');
    }

    public function create(): View
    {
        return view('departments.create', ['department' => new Department]);
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(DataTableQueryBuilder::for(Department::query()->withCount('teams'), $request, [
            'name' => AllowedFilter::partial('name'),
            'description' => AllowedFilter::partial('description'),
        ]))
            ->addColumn('actions', fn (Department $department): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">View</a>',
                e(route('departments.show', $department)),
            ))
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function show(Department $department): View
    {
        $department->load('teams');

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        return view('departments.edit', compact('department'));
    }

    public function store(Request $request): RedirectResponse
    {
        $department = Department::query()->create($this->validatedData($request));

        return redirect()->route('departments.show', $department)->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validatedData($request, $department));

        return redirect()->route('departments.show', $department)->with('success', 'Department updated.');
    }

    public function delete(Department $department): View
    {
        $department->load('teams');

        return view('departments.delete', compact('department'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->teams()->exists()) {
            return redirect()->route('departments.index')
                ->with('error', "Move or delete this department's teams before deleting the department.");
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }

    private function validatedData(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
