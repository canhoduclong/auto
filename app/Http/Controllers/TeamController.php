<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::with('users')->withCount('users')->orderBy('name')->paginate(15);
        $allUsers = \App\Models\User::orderBy('name')->get();
        return view('teams.index', compact('teams', 'allUsers'));
    }

    public function create()
    {
        return view('teams.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'code' => 'nullable|string|max:100|unique:teams,code',
            'note' => 'nullable|string|max:1000',
        ]);

        Team::create($data);

        return redirect()->route('teams.index')->with('success', __('teams.messages.created'));
    }

    public function edit(Team $team)
    {
        return view('teams.edit', compact('team'));
    }

    public function show(Team $team)
    {
        return redirect()->route('teams.edit', $team);
    }

    public function update(Request $request, Team $team)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
            'code' => 'nullable|string|max:100|unique:teams,code,' . $team->id,
            'note' => 'nullable|string|max:1000',
        ]);

        $team->update($data);

        return redirect()->route('teams.index')->with('success', __('teams.messages.updated'));
    }

    public function destroy(Team $team)
    {
        if ($team->users()->exists()) {
            return back()->with('error', __('teams.messages.delete_blocked_has_users'));
        }

        $team->delete();

        return redirect()->route('teams.index')->with('success', __('teams.messages.deleted'));
    }

    public function assignUser(Request $request, Team $team)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = \App\Models\User::find($request->input('user_id'));
        $user->team_id = $team->id;
        $user->save();

        return back()->with('success', "Đã thêm {$user->name} vào team {$team->name}");
    }

    public function removeUser(Request $request, Team $team)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = \App\Models\User::find($request->input('user_id'));
        $user->team_id = null;
        $user->save();

        return back()->with('success', "Đã xóa {$user->name} khỏi team {$team->name}");
    }
}
