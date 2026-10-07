<?php

namespace App\Http\Controllers;

use App\Models\TaskDelegateConfig;
use App\Models\User;
use Illuminate\Http\Request;

class TaskDelegateConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','role:admin']);
    }

    // ── Index ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $request->validate(['assigner_id'=>'nullable|integer|exists:users,id','active'=>'nullable|in:0,1','search'=>'nullable|string|max:200']);
        $configs = TaskDelegateConfig::with([
            'assigner:id,name',
            'assignee:id,name',
            'admin:id,name',
        ])
        ->when($request->filled('assigner_id'), fn ($q) => $q->where('assigner_id', $request->assigner_id))
        ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->active === '1'))
        ->when($request->filled('search'),function($q)use($request){
            $term='%'.$request->search.'%';
            $q->where(fn($s)=>$s->whereHas('assigner',fn($u)=>$u->where('name','like',$term))->orWhereHas('assignee',fn($u)=>$u->where('name','like',$term))->orWhere('note','like',$term));
        })
        ->orderBy('assigner_id')->orderByDesc('is_active')->orderBy('id')
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
            ->with('success', "Đã thêm {$created} phân quyền giao việc.");
    }

    // ── Toggle active ─────────────────────────────────────────────────

    public function toggle(TaskDelegateConfig $taskDelegateConfig)
    {
        $taskDelegateConfig->update(['is_active' => !$taskDelegateConfig->is_active]);

        return back()->with('success', 'Đã cập nhật trạng thái phân quyền.');
    }

    // ── Destroy ───────────────────────────────────────────────────────

    public function destroy(TaskDelegateConfig $taskDelegateConfig)
    {
        $taskDelegateConfig->delete();
        return back()->with('success', 'Đã xóa phân quyền giao việc.');
    }

    // ── Bulk destroy for an assigner ──────────────────────────────────

    public function destroyAssigner(Request $request)
    {
        $request->validate(['assigner_id' => 'required|exists:users,id']);
        $count = TaskDelegateConfig::where('assigner_id', $request->assigner_id)->delete();
        return back()->with('success', "Đã xóa {$count} phân quyền cho người dùng này.");
    }
}
