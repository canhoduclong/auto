<?php

namespace App\Http\Controllers;

use App\Models\TransactionCategory;
use Illuminate\Http\Request;

class TransactionCategoryController extends Controller
{
    public function index()
    {
        $categories = TransactionCategory::query()->orderBy('sort_order')->orderBy('name')->get();
        return view('accounting.transaction_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:transaction_categories,code',
            'name' => 'required|string|max:100',
            'flow_direction' => 'required|in:in,out',
        ]);
        $data['created_by'] = auth()->id();
        $data['sort_order'] = TransactionCategory::max('sort_order') + 1;

        $cat = TransactionCategory::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $cat->id,
                'code' => $cat->code,
                'name' => $cat->name,
                'flow_direction' => $cat->flow_direction,
                'flow_label' => $cat->flowDirectionLabel(),
            ]);
        }

        return redirect()->route('accounting.transaction-categories.index')->with('success', 'Đã thêm danh mục giao dịch.');
    }

    public function update(Request $request, TransactionCategory $transactionCategory)
    {
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:transaction_categories,code,' . $transactionCategory->id,
            'name' => 'required|string|max:100',
            'flow_direction' => 'required|in:in,out',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $transactionCategory->update($data);

        return redirect()->route('accounting.transaction-categories.index')->with('success', 'Đã cập nhật danh mục giao dịch.');
    }

    public function toggleActive(TransactionCategory $transactionCategory)
    {
        $transactionCategory->update(['is_active' => ! $transactionCategory->is_active]);

        return redirect()->route('accounting.transaction-categories.index')->with('success', 'Đã cập nhật trạng thái danh mục.');
    }
}
