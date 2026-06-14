<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OrderController;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\TextOrderDraft;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\ZaloOrderTextParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TextOrderImportController extends Controller
{
    public function index()
    {
        $drafts = TextOrderDraft::query()
            ->with(['sale:id,name,zalo_name', 'customer:id,name,phone', 'variant.product:id,name', 'order:id,code'])
            ->latest()
            ->limit(100)
            ->get();
        $sales = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'sale'))->orderBy('name')->get(['id', 'name', 'zalo_name']);
        $variants = ProductVariant::query()->with('product:id,name')->orderBy('name')->get(['id', 'product_id', 'name', 'sku', 'size']);

        return view('admin.text-order-import.index', compact('drafts', 'sales', 'variants'));
    }

    public function parse(Request $request, ZaloOrderTextParser $parser)
    {
        $validated = $request->validate(['text' => ['required', 'string', 'max:200000']]);
        $parsed = $parser->parse($validated['text']);

        foreach ($parsed as $data) {
            TextOrderDraft::query()->create(array_merge($data, ['created_by' => $request->user()->id]));
        }

        return back()->with('success', 'Đã nhận diện ' . $parsed->count() . ' đơn nháp từ nội dung Zalo.');
    }

    public function confirm(Request $request, TextOrderDraft $draft, ApprovalService $approvalService): JsonResponse
    {
        try {
            $order = $this->confirmDraft($request, $draft, $approvalService);
            return response()->json(['message' => 'Đã xác nhận đơn ' . ($order->code ?: '#' . $order->id), 'order_id' => $order->id]);
        } catch (\Throwable $exception) {
            $draft->update(['status' => 'error', 'error_message' => $exception->getMessage()]);
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function bulkConfirm(Request $request, ApprovalService $approvalService): JsonResponse
    {
        $validated = $request->validate([
            'draft_ids' => ['required', 'array', 'min:1'],
            'draft_ids.*' => ['integer', 'exists:text_order_drafts,id'],
        ]);

        $confirmed = 0;
        $failed = [];
        foreach (TextOrderDraft::query()->whereIn('id', $validated['draft_ids'])->get() as $draft) {
            try {
                $this->confirmDraft($request, $draft, $approvalService);
                $confirmed++;
            } catch (\Throwable $exception) {
                $draft->update(['status' => 'error', 'error_message' => $exception->getMessage()]);
                $failed[] = '#' . $draft->id . ': ' . $exception->getMessage();
            }
        }

        return response()->json([
            'message' => 'Đã xác nhận ' . $confirmed . ' đơn.' . ($failed ? ' Lỗi: ' . implode(' | ', $failed) : ''),
        ]);
    }

    private function confirmDraft(Request $request, TextOrderDraft $draft, ApprovalService $approvalService)
    {
        abort_if($draft->status === 'confirmed', 422, 'Đơn nháp này đã được xác nhận.');

        $validated = $request->validate([
            'sale_id' => ['nullable', 'exists:users,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'size_kg' => ['nullable', 'numeric', 'min:0.01'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'delivery_date' => ['nullable', 'date'],
            'delivery_time' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:10000'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.size_kg' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.product_text' => ['nullable', 'string', 'max:500'],
        ]);
        if (isset($validated['items'])) {
            $validated['parsed_items'] = $validated['items'];
            unset($validated['items']);
        }
        $draft->fill($validated)->save();
        $draft->refresh();

        if (!$draft->sale_id) {
            throw new \RuntimeException('Chưa nhận diện sale. Hãy chọn sale hoặc cập nhật tên Zalo của sale.');
        }
        $draftItems = collect($draft->parsed_items ?: [[
            'product_variant_id' => $draft->product_variant_id,
            'quantity' => $draft->quantity,
            'size_kg' => $draft->size_kg,
            'unit_price' => $draft->unit_price,
        ]]);
        if ($draftItems->contains(fn ($item) => empty($item['product_variant_id']) || empty($item['quantity']))) {
            throw new \RuntimeException('Có sản phẩm chưa nhận diện biến thể hoặc số lượng.');
        }

        $customer = $draft->customer;
        if (!$customer) {
            if (!$draft->customer_name && !$draft->phone) {
                throw new \RuntimeException('Thiếu tên và số điện thoại khách hàng.');
            }
            $customer = Customer::query()->create([
                'user_id' => $draft->sale_id,
                'assigned_to' => $draft->sale_id,
                'current_owner_sale_id' => $draft->sale_id,
                'name' => $draft->customer_name ?: ('Khách ' . $draft->phone),
                'phone' => $draft->phone,
                'address' => $draft->address,
                'delivery_time' => $draft->delivery_time,
                'status' => 'active',
            ]);
            $draft->update(['customer_id' => $customer->id]);
        }

        return DB::transaction(function () use ($draft, $customer, $draftItems, $approvalService) {
            $order = app(OrderController::class)->createOrderFromSchedule(
                $draftItems->map(fn ($item) => [
                    'variant_id' => (int) $item['product_variant_id'],
                    'quantity' => (int) $item['quantity'],
                    'base_price' => isset($item['unit_price']) ? (float) $item['unit_price'] : null,
                    'unit_discount' => 0,
                    'unit_discount_type' => 'decrease',
                    'unit_weight' => isset($item['size_kg']) ? (float) $item['size_kg'] : null,
                ])->all(),
                [
                    'customer_id' => $customer->id,
                    'user_id' => $draft->sale_id,
                    'recipient_name' => $draft->customer_name ?: $customer->name,
                    'recipient_phone' => $draft->phone ?: $customer->phone,
                    'recipient_address' => $draft->address ?: $customer->address,
                    'note' => $draft->note,
                    'delivery_date' => optional($draft->delivery_date)->toDateString(),
                    'delivery_time' => $draft->delivery_time,
                    'status' => OrderStatus::Pending->value,
                    'payment_status' => PaymentStatus::Unpaid->value,
                    'delivery_status' => DeliveryStatus::NotShipped->value,
                    'allow_backorder' => true,
                ],
                $approvalService
            );

            $draft->update(['status' => 'confirmed', 'order_id' => $order->id, 'error_message' => null]);
            return $order;
        });
    }
}
