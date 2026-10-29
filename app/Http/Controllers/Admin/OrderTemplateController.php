<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ProductVariant, TextOrderDraft, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $sales = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale']))
            ->orderBy('name')->get(['id', 'name']);
        return view('admin.order-templates.index', compact('drafts', 'sales', 'counts'));
    }

    public function copyToSale(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $eligibleSaleIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale']))->pluck('id')->all();
        $data = $request->validate([
            'sale_id' => ['required', 'integer', Rule::exists('users', 'id')->whereIn('id', $eligibleSaleIds)],
            'draft_ids' => ['required', 'array', 'min:1'],
            'draft_ids.*' => ['required', 'integer', 'distinct', Rule::exists('text_order_drafts', 'id')->where('draft_scope', TextOrderDraft::SCOPE_SALE_PRIVATE)],
        ]);

        $copies = DB::transaction(function () use ($data) {
            $drafts = TextOrderDraft::query()->where('draft_scope', TextOrderDraft::SCOPE_SALE_PRIVATE)
                ->whereIn('id', $data['draft_ids'])->lockForUpdate()->get();
            abort_if($drafts->count() !== count($data['draft_ids']), 422, 'Một số mẫu không còn tồn tại. Tải lại trang và thử lại.');
            foreach ($drafts as $draft) {
                $copy = $draft->replicate(['order_id', 'status', 'error_message', 'automation_mode', 'automation_enabled', 'automation_dates', 'automation_last_run_at', 'automation_last_error']);
                $copy->sale_id = $data['sale_id'];
                $copy->created_by = $data['sale_id'];
                $copy->order_id = null;
                $copy->status = 'draft';
                $copy->error_message = null;
                $copy->automation_mode = null;
                $copy->automation_enabled = false;
                $copy->automation_dates = null;
                $copy->automation_last_run_at = null;
                $copy->automation_last_error = null;
                $copy->save();
            }
            return $drafts->count();
        });

        return redirect()->route('admin.order-templates.index', $request->query())
            ->with('success', "Đã sao chép {$copies} mẫu sang sale được chọn. Lịch tự động của các mẫu mới đang tắt.");
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
