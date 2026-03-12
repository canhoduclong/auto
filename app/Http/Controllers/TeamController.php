<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::withCount('users')->orderBy('name')->paginate(15);
        return view('teams.index', compact('teams'));
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

        return redirect()->route('teams.index')->with('success', 'Tạo team thành công.');
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

        return redirect()->route('teams.index')->with('success', 'Cập nhật team thành công.');
    }

    public function destroy(Team $team)
    {
        if ($team->users()->exists()) {
            return back()->with('error', 'Không thể xóa team đang có user.');
        }

        $team->delete();

        return redirect()->route('teams.index')->with('success', 'Xóa team thành công.');
    }
}
