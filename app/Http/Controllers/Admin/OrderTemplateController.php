<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ProductVariant, TextOrderDraft, User};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderTemplateController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'sale_id' => 'nullable|integer|exists:users,id', 'search' => 'nullable|string|max:150',
            'automation' => ['nullable', Rule::in(['on', 'off', 'daily', 'scheduled', 'error'])],
        ]);
        $query = TextOrderDraft::query()->where('draft_scope', TextOrderDraft::SCOPE_SALE_PRIVATE);
        $counts = [
            'all' => (clone $query)->count(), 'enabled' => (clone $query)->where('automation_enabled', true)->count(),
            'errors' => (clone $query)->whereNotNull('automation_last_error')->where('automation_last_error', '!=', '')->count(),
        ];
        $search = trim($data['search'] ?? '');
        $drafts = $query->with(['sale:id,name', 'customer:id,name,customer_code,phone', 'variant.product:id,name', 'order:id,code'])
            ->when($data['sale_id'] ?? null, fn ($q, $id) => $q->where('sale_id', $id))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('customer_code', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%'));
            }))
            ->when(($data['automation'] ?? '') === 'on', fn ($q) => $q->where('automation_enabled', true))
            ->when(($data['automation'] ?? '') === 'off', fn ($q) => $q->where('automation_enabled', false))
            ->when(in_array($data['automation'] ?? '', ['daily', 'scheduled']), fn ($q) => $q->where('automation_enabled', true)->where('automation_mode', $data['automation']))
            ->when(($data['automation'] ?? '') === 'error', fn ($q) => $q->whereNotNull('automation_last_error')->where('automation_last_error', '!=', ''))
            ->latest('id')->paginate(20)->withQueryString();
        $sales = User::whereIn('id', TextOrderDraft::where('draft_scope', TextOrderDraft::SCOPE_SALE_PRIVATE)->select('sale_id'))->orderBy('name')->get(['id', 'name']);
        return view('admin.order-templates.index', compact('drafts', 'sales', 'counts'));
    }

    public function edit(Request $request, TextOrderDraft $draft)
    {
        abort_unless($request->user()?->isAdmin() && $draft->draft_scope === TextOrderDraft::SCOPE_SALE_PRIVATE, 403);
        $draft->load(['sale:id,name', 'customer:id,name,phone', 'variant.product:id,name']);
        $items = $draft->parsed_items ?: [[
            'product_variant_id' => $draft->product_variant_id, 'quantity' => $draft->quantity,
            'size_kg' => $draft->size_kg, 'unit_price' => $draft->unit_price, 'product_text' => $draft->product_text,
        ]];
        $variants = ProductVariant::with('product:id,name')->whereIn('id', array_column($items, 'product_variant_id'))->get()->keyBy('id');
        return view('admin.order-templates.edit', compact('draft', 'items', 'variants'));
    }
}
