<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('teams.departments');
    }

    public function datatable()
    {
        return DataTables::eloquent(Department::query()->withCount('teams'))
            ->addColumn('actions', fn (Department $department): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">Edit</a>',
                e(route('departments.edit', $department)),
            ))
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function edit(Department $department): View
    {
        return view('teams.department-edit', compact('department'));
    }

    public function store(Request $request): RedirectResponse
    {
        Department::query()->create($this->validatedData($request));

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validatedData($request, $department));

        return redirect()->route('departments.index')->with('success', 'Department updated.');
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
