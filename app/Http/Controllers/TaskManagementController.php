<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['assigner', 'assignee', 'customer']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by assignee
        if ($request->filled('assignee_id')) {
            $query->where('assigned_to', $request->assignee_id);
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $tasks = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get users for filters
        $users = User::select('id', 'name')->orderBy('name')->get();

        // Task statistics
        $stats = [
            'total' => Task::count(),
            'pending' => Task::where('status', 'pending')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'completed' => Task::where('status', 'completed')->count(),
            'overdue' => Task::overdue()->count(),
        ];

        return view('ceo.task-management', compact('tasks', 'users', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:kpi,customer_task,general',
            'assigned_to' => 'required|exists:users,id',
            'deadline' => 'nullable|date|after_or_equal:today',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $validated['assigned_by'] = Auth::id();

        Task::create($validated);

        return redirect()->back()->with('success', 'Task đã được tạo thành công!');
    }

    public function update(Request $request, Task $task)
    {
        // Only assignee can update note and next_appointment
        if ($request->filled('note') || $request->filled('next_appointment')) {
            $validated = $request->validate([
                'note' => 'nullable|string',
                'next_appointment' => 'nullable|date|after_or_equal:today',
            ]);

            $task->update($validated);
            return redirect()->back()->with('success', 'Task đã được cập nhật!');
        }

        // CEO/Manager can update status
        if ($request->filled('status')) {
            $validated = $request->validate([
                'status' => 'required|in:pending,in_progress,completed,overdue,cancelled',
            ]);

            $updateData = ['status' => $validated['status']];

            if ($validated['status'] === 'completed') {
                $updateData['completed_at'] = now();
            }

            $task->update($updateData);
            return redirect()->back()->with('success', 'Trạng thái task đã được cập nhật!');
        }

        return redirect()->back()->with('error', 'Không có gì để cập nhật!');
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->back()->with('success', 'Task đã được xóa!');
    }

    // API endpoints for AJAX updates
    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,overdue,cancelled',
        ]);

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'completed') {
            $updateData['completed_at'] = now();
        }

        $task->update($updateData);

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
    }

    public function getCustomers(Request $request)
    {
        $customers = Customer::select('id', 'name', 'phone')
            ->where('name', 'like', '%' . $request->q . '%')
            ->orWhere('phone', 'like', '%' . $request->q . '%')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }
}
