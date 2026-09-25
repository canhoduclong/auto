<?php

namespace App\Http\Controllers;

use App\Models\TaskDelegateConfig;
use App\Models\User;
use Illuminate\Http\Request;

class TaskDelegateConfigController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $configs = TaskDelegateConfig::with([
            'assigner:id,name',
            'assignee:id,name',
            'admin:id,name',
        ])
        ->when($request->filled('assigner_id'), fn ($q) => $q->where('assigner_id', $request->assigner_id))
        ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->active === '1'))
        ->orderBy('assigner_id')
        ->paginate(30)
        ->withQueryString();

        // Group by assigner for display
        $grouped = $configs->getCollection()->groupBy('assigner_id');

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('task_delegate_configs.index', compact('configs', 'grouped', 'users'));
    }

    // ── Create ────────────────────────────────────────────────────────

    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name']);
        return view('task_delegate_configs.create', compact('users'));
    }

    // ── Store ─────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'assigner_id'  => 'required|exists:users,id',
            'assignee_ids' => 'required|array|min:1',
            'assignee_ids.*' => 'exists:users,id|different:assigner_id',
            'note'         => 'nullable|string|max:500',
        ]);

        $created = 0;
        foreach ($data['assignee_ids'] as $assigneeId) {
            TaskDelegateConfig::firstOrCreate(
                [
                    'assigner_id' => $data['assigner_id'],
                    'assignee_id' => $assigneeId,
                ],
                [
                    'is_active'  => true,
                    'created_by' => auth()->id(),
                    'note'       => $data['note'] ?? null,
                ]
            );
            $created++;
        }

        return redirect()->route('task-delegate-configs.index')
            ->with('success', "Da them {$created} phan quyen giao viec.");
    }

    // ── Toggle active ─────────────────────────────────────────────────

    public function toggle(TaskDelegateConfig $taskDelegateConfig)
    {
        $taskDelegateConfig->update(['is_active' => !$taskDelegateConfig->is_active]);

        return back()->with('success', 'Da cap nhat trang thai phan quyen.');
    }

    // ── Destroy ───────────────────────────────────────────────────────

    public function destroy(TaskDelegateConfig $taskDelegateConfig)
    {
        $taskDelegateConfig->delete();
        return back()->with('success', 'Da xoa phan quyen giao viec.');
    }

    // ── Bulk destroy for an assigner ──────────────────────────────────

    public function destroyAssigner(Request $request)
    {
        $request->validate(['assigner_id' => 'required|exists:users,id']);
        $count = TaskDelegateConfig::where('assigner_id', $request->assigner_id)->delete();
        return back()->with('success', "Da xoa {$count} phan quyen cho nguoi dung nay.");
    }
}
