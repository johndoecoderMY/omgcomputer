<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class TeamController extends Controller
{
    public function index(): View
    {
        return view('teams.index', [
            'departments' => Department::query()->orderBy('name')->get(),
            'users' => User::query()->with('teams')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function datatable()
    {
        return DataTables::eloquent(
            Team::query()
                ->join('departments', 'teams.department_id', '=', 'departments.id')
                ->select('teams.*', 'departments.name as department_name')
                ->with('users')
                ->withCount('users')
        )
            ->addColumn('members', fn (Team $team): string => $team->users
                ->map(fn (User $user): string => sprintf(
                    '<a href="%s">%s</a>',
                    e(route('members.show', $user)),
                    e($user->name),
                ))
                ->join(', '))
            ->addColumn('actions', fn (Team $team): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">Manage</a>',
                e(route('teams.edit', $team)),
            ))
            ->rawColumns(['members', 'actions'])
            ->toJson();
    }

    public function edit(Team $team): View
    {
        return view('teams.edit', [
            'team' => $team->load('users'),
            'departments' => Department::query()->orderBy('name')->get(),
            'users' => User::query()->with('teams')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $team = DB::transaction(function () use ($data): Team {
            $team = Team::query()->create(collect($data)->except('user_ids')->all());
            $this->syncMembers($team, $data['user_ids'] ?? []);

            return $team;
        });

        return redirect()->route('teams.index')->with('success', "Team {$team->name} created.");
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $data = $this->validatedData($request, $team);
        DB::transaction(function () use ($team, $data): void {
            $team->update(collect($data)->except('user_ids')->all());
            $this->syncMembers($team, $data['user_ids'] ?? []);
        });

        return redirect()->route('teams.index')->with('success', "Team {$team->name} updated.");
    }

    public function destroy(Team $team): RedirectResponse
    {
        $team->users()->detach();
        $team->delete();

        return redirect()->route('teams.index')->with('success', 'Team deleted; members remain assigned to their other teams.');
    }

    private function validatedData(Request $request, ?Team $team = null): array
    {
        return $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams', 'name')
                    ->where('department_id', $request->input('department_id'))
                    ->ignore($team?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);
    }

    private function syncMembers(Team $team, array $userIds): void
    {
        $currentUserIds = $team->users()->pluck('users.id')->all();
        $team->users()->detach(array_diff($currentUserIds, $userIds));
        $team->users()->syncWithoutDetaching($userIds);
    }
}
