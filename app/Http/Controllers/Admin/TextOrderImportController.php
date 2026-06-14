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
use App\Models\TruckBrand;
use App\Models\TruckStation;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\CustomerPriorityService;
use App\Services\ZaloOrderTextParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TextOrderImportController extends Controller
{
    public function index()
    {
        return $this->draftIndex();
    }

    public function saleIndex(Request $request)
    {
        return $this->draftIndex((int) $request->user()->id);
    }

    private function draftIndex(?int $saleId = null)
    {
        $drafts = TextOrderDraft::query()
            ->with(['sale:id,name,zalo_name', 'customer:id,name,phone', 'truckBrand:id,name', 'truckStation:id,name,address,brand_id', 'variant.product:id,name', 'order:id,code'])
            ->where('draft_scope', $saleId ? TextOrderDraft::SCOPE_SALE_PRIVATE : TextOrderDraft::SCOPE_ADMIN_IMPORT)
            ->when($saleId, fn ($query) => $query->where('sale_id', $saleId))
            ->latest()
            ->limit(100)
            ->get();
        $sales = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'sale'))->orderBy('name')->get(['id', 'name', 'zalo_name']);
        $variants = ProductVariant::query()
            ->with('product:id,name,kg')
            ->orderBy('name')
            ->get(['id', 'product_id', 'name', 'sku', 'size', 'kg']);
        $truckStations = TruckStation::query()
            ->with('brand:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'brand_id', 'name', 'address']);

        $saleMode = $saleId !== null;
        $pageTitle = $saleMode ? 'Đơn nháp' : 'Nhập đơn text';
        $actionBaseUrl = $saleMode ? url('my-order-drafts') : url('admin/text-order-import');
        $parseRoute = $saleMode ? route('pages.my_order_drafts.parse') : route('admin.text-order-import.parse');

        return view('admin.text-order-import.index', compact(
            'drafts', 'sales', 'variants', 'truckStations', 'saleMode', 'pageTitle', 'actionBaseUrl', 'parseRoute'
        ));
    }

    public function parse(Request $request, ZaloOrderTextParser $parser)
    {
        $validated = $request->validate(['text' => ['required', 'string', 'max:200000']]);
        $parsed = $parser->parse($validated['text']);

        foreach ($parsed as $data) {
            TextOrderDraft::query()->create(array_merge($data, [
                'created_by' => $data['sale_id'] ?? $request->user()->id,
                'draft_scope' => TextOrderDraft::SCOPE_ADMIN_IMPORT,
            ]));
        }

        return back()->with('success', 'Đã nhận diện ' . $parsed->count() . ' đơn nháp từ nội dung Zalo.');
    }

    public function saleParse(Request $request, ZaloOrderTextParser $parser)
    {
        $validated = $request->validate(['text' => ['required', 'string', 'max:200000']]);
        $saleId = (int) $request->user()->id;
        $parsed = $parser->parse($validated['text']);

        foreach ($parsed as $data) {
            TextOrderDraft::query()->create(array_merge($data, [
                'sale_id' => $saleId,
                'created_by' => $saleId,
                'draft_scope' => TextOrderDraft::SCOPE_SALE_PRIVATE,
            ]));
        }

        return back()->with('success', 'Đã nhận diện ' . $parsed->count() . ' đơn nháp của bạn.');
    }

    public function saleConfirm(Request $request, TextOrderDraft $draft, ApprovalService $approvalService): JsonResponse
    {
        $this->ensureSaleDraft($request, $draft);
        $this->forceSale($request);

        return $this->confirmAction($request, $draft, $approvalService);
    }

    public function saleCopy(Request $request, TextOrderDraft $draft): JsonResponse
    {
        $this->ensureSaleDraft($request, $draft);
        $this->forceSale($request);

        return $this->copyAction($request, $draft);
    }

    public function saleCopyConfirm(Request $request, TextOrderDraft $draft, ApprovalService $approvalService): JsonResponse
    {
        $this->ensureSaleDraft($request, $draft);
        $this->forceSale($request);

        return $this->copyConfirmAction($request, $draft, $approvalService);
    }

    public function saleDestroy(Request $request, TextOrderDraft $draft): JsonResponse
    {
        $this->ensureSaleDraft($request, $draft);

        return $this->destroyAction($draft);
    }

    public function confirm(Request $request, TextOrderDraft $draft, ApprovalService $approvalService): JsonResponse
    {
        $this->ensureAdminDraft($draft);

        return $this->confirmAction($request, $draft, $approvalService);
    }

    private function confirmAction(Request $request, TextOrderDraft $draft, ApprovalService $approvalService): JsonResponse
    {
        try {
            $order = $this->confirmDraft($request, $draft, $approvalService);
            return response()->json([
                'message' => 'Đã xác nhận đơn ' . ($order->code ?: '#' . $order->id),
                'order_id' => $order->id,
                'order_code' => $order->code,
                'delivery_date' => $this->today(),
            ]);
        } catch (\Throwable $exception) {
            $draft->update(['status' => 'error', 'error_message' => $exception->getMessage()]);
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function copy(Request $request, TextOrderDraft $draft): JsonResponse
    {
        $this->ensureAdminDraft($draft);

        return $this->copyAction($request, $draft);
    }

    private function copyAction(Request $request, TextOrderDraft $draft): JsonResponse
    {
        try {
            $copy = $this->copyDraft($request, $draft);

            return response()->json([
                'message' => 'Đã sao chép thành bản nháp #' . $copy->id . ' cho sale.',
                'draft_id' => $copy->id,
                'delivery_date' => $this->today(),
            ]);
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function copyConfirm(Request $request, TextOrderDraft $draft, ApprovalService $approvalService): JsonResponse
    {
        $this->ensureAdminDraft($draft);

        return $this->copyConfirmAction($request, $draft, $approvalService);
    }

    private function copyConfirmAction(Request $request, TextOrderDraft $draft, ApprovalService $approvalService): JsonResponse
    {
        try {
            [$copy, $order] = DB::transaction(function () use ($request, $draft, $approvalService) {
                $copy = $this->copyDraft($request, $draft);
                $order = $this->confirmDraft($request, $copy, $approvalService);

                return [$copy, $order];
            });
            return response()->json([
                'message' => 'Đã sao chép và xác nhận đơn ' . ($order->code ?: '#' . $order->id),
                'draft_id' => $copy->id,
                'order_id' => $order->id,
                'order_code' => $order->code,
                'delivery_date' => $this->today(),
            ]);
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(TextOrderDraft $draft): JsonResponse
    {
        $this->ensureAdminDraft($draft);

        return $this->destroyAction($draft);
    }

    private function destroyAction(TextOrderDraft $draft): JsonResponse
    {
        $draft->delete();

        return response()->json(['message' => 'Đã xóa dòng import. Đơn sale đã tạo (nếu có) không bị ảnh hưởng.']);
    }

    public function bulkConfirm(Request $request, ApprovalService $approvalService): JsonResponse
    {
        $validated = $request->validate([
            'draft_ids' => ['required', 'array', 'min:1'],
            'draft_ids.*' => [
                'integer',
                Rule::exists('text_order_drafts', 'id')
                    ->where('draft_scope', TextOrderDraft::SCOPE_ADMIN_IMPORT),
            ],
        ]);

        $confirmed = 0;
        $failed = [];
        foreach (TextOrderDraft::query()
            ->where('draft_scope', TextOrderDraft::SCOPE_ADMIN_IMPORT)
            ->whereIn('id', $validated['draft_ids'])
            ->get() as $draft) {
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

        $draft->fill($this->validatedDraftData($request));
        $draft->delivery_date = $this->today();
        if ($draft->sale_id) {
            $draft->created_by = $draft->sale_id;
        }
        $draft->save();
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

        $truckStation = $this->resolveTruckStation($draft);
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
                'use_truck_station' => (bool) $truckStation,
                'truck_station_id' => $truckStation?->id,
                'truck_station_address' => $draft->truck_station_address ?: $truckStation?->address,
                'status' => 'active',
            ]);
            $draft->update(['customer_id' => $customer->id]);
            app(CustomerPriorityService::class)->attachSale($customer, (int) $draft->sale_id, 1, 'text_order_import');
        } elseif ($truckStation) {
            $customer->update([
                'use_truck_station' => true,
                'truck_station_id' => $truckStation->id,
                'truck_station_address' => $draft->truck_station_address ?: $truckStation->address,
            ]);
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
                    'actor_user_id' => $draft->sale_id,
                    'recipient_name' => $draft->customer_name ?: $customer->name,
                    'recipient_phone' => $draft->phone ?: $customer->phone,
                    'recipient_address' => $draft->address ?: $customer->address,
                    'note' => $draft->note,
                    'delivery_date' => $this->today(),
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

    private function copyDraft(Request $request, TextOrderDraft $draft): TextOrderDraft
    {
        $data = $this->validatedDraftData($request);
        if (empty($data['sale_id'])) {
            throw new \RuntimeException('Hãy chọn sale nhận đơn trước khi sao chép.');
        }

        $copy = $draft->replicate(['order_id', 'status', 'error_message']);
        $copy->fill($data);
        $copy->created_by = $data['sale_id'];
        $copy->delivery_date = $this->today();
        $copy->order_id = null;
        $copy->status = 'draft';
        $copy->error_message = null;
        $copy->save();

        return $copy;
    }

    private function validatedDraftData(Request $request): array
    {
        $validated = $request->validate([
            'sale_id' => ['nullable', 'exists:users,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'truck_brand_id' => ['nullable', 'exists:truck_brands,id'],
            'truck_station_id' => ['nullable', 'exists:truck_stations,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'truck_brand_name' => ['nullable', 'string', 'max:255'],
            'truck_station_address' => ['nullable', 'string', 'max:255'],
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

        return $validated;
    }

    private function resolveTruckStation(TextOrderDraft $draft): ?TruckStation
    {
        if ($draft->truck_station_id) {
            return TruckStation::query()->find($draft->truck_station_id);
        }

        $brandName = trim((string) $draft->truck_brand_name);
        $stationAddress = trim((string) $draft->truck_station_address);
        if ($brandName === '' && $stationAddress === '') {
            return null;
        }
        if ($brandName === '') {
            throw new \RuntimeException('Có địa chỉ trạm xe nhưng chưa có tên nhà xe.');
        }

        $normalizedBrand = Str::of($brandName)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
        $brand = $draft->truck_brand_id
            ? TruckBrand::query()->find($draft->truck_brand_id)
            : TruckBrand::query()->get()->first(fn (TruckBrand $item) =>
                Str::of($item->name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString() === $normalizedBrand
            );
        $brand ??= TruckBrand::query()->create([
            'name' => $brandName,
            'is_active' => true,
            'created_by' => $draft->sale_id,
        ]);

        $normalizedAddress = Str::of($stationAddress)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
        $station = TruckStation::query()
            ->where('brand_id', $brand->id)
            ->get()
            ->first(fn (TruckStation $item) => $normalizedAddress !== '' && (
                Str::of((string) $item->address)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString() === $normalizedAddress
                || Str::of($item->name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString() === $normalizedAddress
            ));
        $station ??= TruckStation::query()->create([
            'name' => $brand->name . ($stationAddress !== '' ? ' - ' . $stationAddress : ''),
            'brand_id' => $brand->id,
            'address' => $stationAddress ?: null,
            'is_active' => true,
            'created_by' => $draft->sale_id,
        ]);

        $draft->update([
            'truck_brand_id' => $brand->id,
            'truck_station_id' => $station->id,
            'truck_brand_name' => $brand->name,
            'truck_station_address' => $stationAddress ?: $station->address,
        ]);

        return $station;
    }

    private function today(): string
    {
        return Carbon::now('Asia/Bangkok')->toDateString();
    }

    private function ensureSaleDraft(Request $request, TextOrderDraft $draft): void
    {
        abort_unless(
            $draft->draft_scope === TextOrderDraft::SCOPE_SALE_PRIVATE
            && (int) $draft->sale_id === (int) $request->user()->id,
            403
        );
    }

    private function ensureAdminDraft(TextOrderDraft $draft): void
    {
        abort_unless($draft->draft_scope === TextOrderDraft::SCOPE_ADMIN_IMPORT, 403);
    }

    private function forceSale(Request $request): void
    {
        $request->merge(['sale_id' => (int) $request->user()->id]);
    }
}
