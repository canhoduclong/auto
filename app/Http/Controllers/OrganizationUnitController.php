<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationUnitController extends Controller
{
    public function index()
    {
        $blocks = Block::query()
            ->withCount(['departments', 'users'])
            ->with(['departments' => fn ($query) => $query->withCount('users')->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('admin.organization-units.index', [
            'blocks' => $blocks,
        ]);
    }

    public function storeBlock(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('blocks', 'name')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Block::create([
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Đã thêm khối.');
    }

    public function updateBlock(Request $request, Block $block)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('blocks', 'name')->ignore($block->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $block->update([
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Đã cập nhật khối.');
    }

    public function destroyBlock(Block $block)
    {
        if ($block->departments()->exists() || $block->users()->exists()) {
            return back()->with('error', 'Không thể xóa khối đang có phòng ban hoặc user.');
        }

        $block->delete();

        return back()->with('success', 'Đã xóa khối.');
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->where(fn ($query) => $query->where('block_id', $request->input('block_id'))),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Department::create([
            'block_id' => $validated['block_id'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Đã thêm phòng ban.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $validated = $request->validate([
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where(fn ($query) => $query->where('block_id', $request->input('block_id')))
                    ->ignore($department->id),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $department->update([
            'block_id' => $validated['block_id'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Đã cập nhật phòng ban.');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->users()->exists()) {
            return back()->with('error', 'Không thể xóa phòng ban đang có user.');
        }

        $department->delete();

        return back()->with('success', 'Đã xóa phòng ban.');
    }
}
