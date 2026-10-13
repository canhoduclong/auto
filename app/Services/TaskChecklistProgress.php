<?php
namespace App\Services;

use App\Models\TaskAssignment;

class TaskChecklistProgress
{
    public function summarize(TaskAssignment $root): array
    {
        $leaves = collect();
        $seen = [];
        $walk = function ($task, bool $isRoot = false) use (&$walk, &$seen, $leaves) {
            if (isset($seen[$task->id]) || $task->status === 'cancelled') return;
            $seen[$task->id] = true;
            $children = $task->subTasks()->where('status', '!=', 'cancelled')->with('assignees.user.department')->get();
            if ($children->isNotEmpty()) {
                foreach ($children as $child) $walk($child);
            } elseif (!$isRoot) {
                $task->loadMissing('assignees.user.department');
                $leaves->push($task);
            }
        };
        $walk($root, true);
        $people = [];
        foreach ($leaves as $task) {
            foreach ($task->assignees->where('status', '!=', 'cancelled') as $assignment) {
                $id = $assignment->user_id;
                $people[$id] ??= ['name' => $assignment->user?->name ?? 'Người dùng đã xóa', 'department' => $assignment->user?->department?->name ?? 'Chưa có bộ phận', 'total' => 0, 'done' => 0, 'awaiting' => 0];
                $people[$id]['total']++;
                if ($task->status === 'done') $people[$id]['done']++;
                elseif ($assignment->status === 'completed' || $task->status === 'completed') $people[$id]['awaiting']++;
            }
        }
        foreach ($people as &$person) $person['percent'] = round($person['done'] * 100 / $person['total'], 1);
        unset($person);
        $done = $leaves->where('status', 'done')->count();
        return ['items' => $leaves, 'total' => $leaves->count(), 'done' => $done, 'awaiting' => $leaves->where('status', 'completed')->count(), 'percent' => $leaves->count() ? round($done * 100 / $leaves->count(), 1) : 0, 'people' => $people];
    }
}
