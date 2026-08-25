<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderFee;
use App\Models\OrderFeeType;
use Illuminate\Support\Collection;

class OrderFeeService
{
    private const SYSTEM_CODES = ['vat', 'shipping', 'discount', 'foam_box'];

    public function availableTypesForOrder(Order $order): Collection
    {
        if (! $order->relationLoaded('additionalFees')) {
            $order->load('additionalFees');
        }
        $appliedTypeIds = $order->additionalFees->pluck('order_fee_type_id')->filter()->all();

        return OrderFeeType::query()
            ->where(function ($query) use ($appliedTypeIds): void {
                $query->where('is_active', true)
                    ->orWhere('is_system', true);
                if ($appliedTypeIds !== []) {
                    $query->orWhereIn('id', $appliedTypeIds);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (OrderFeeType $type): bool => $type->is_active || $this->currentState($order, $type)['enabled'])
            ->values();
    }

    public function prepareChanges(Order $order, Collection $types, array $submittedFees): array
    {
        return $types->mapWithKeys(function (OrderFeeType $type) use ($order, $submittedFees): array {
            $original = $this->currentState($order, $type);
            $calculationType = $this->effectiveCalculationType($type);
            $submitted = (array) ($submittedFees[$type->id] ?? []);
            $enabled = array_key_exists('enabled', $submitted)
                ? filter_var($submitted['enabled'], FILTER_VALIDATE_BOOLEAN)
                : $original['enabled'];
            $value = array_key_exists('value', $submitted)
                ? (float) $submitted['value']
                : $original['value'];
            $max = $calculationType === OrderFeeType::CALCULATION_PERCENT ? 100 : 999999999999.99;
            $value = round(min(max($value, 0), $max), 2);

            return [$type->code => [
                'fee_type_id' => $type->id,
                'name' => $type->name,
                'calculation_type' => $calculationType,
                'direction' => $type->direction,
                'is_system' => $type->is_system,
                'original' => $original,
                'adjusted' => ['enabled' => $enabled, 'value' => $enabled ? $value : 0],
            ]];
        })->all();
    }

    public function currentState(Order $order, OrderFeeType $type): array
    {
        return match ($type->code) {
            'vat' => ['enabled' => (bool) ($order->charge_vat ?? false), 'value' => (float) ($order->vat_amount ?? 0)],
            'shipping' => ['enabled' => (bool) ($order->charge_shipping_fee ?? false), 'value' => (float) ($order->shipping_fee ?? 0)],
            'discount' => ['enabled' => (float) ($order->extra_discount_total ?? 0) > 0, 'value' => max(0, (float) ($order->extra_discount_total ?? 0))],
            'foam_box' => ['enabled' => (bool) ($order->charge_foam_box_fee ?? false), 'value' => (float) ($order->foam_box_price ?? 0)],
            default => $this->customCurrentState($order, $type),
        };
    }

    public function applySystemChanges(Order $order, array $changes): void
    {
        $updates = [];

        if (isset($changes['vat']['adjusted'])) {
            $state = $changes['vat']['adjusted'];
            $updates['charge_vat'] = (bool) ($state['enabled'] ?? false);
            if (($changes['vat']['calculation_type'] ?? OrderFeeType::CALCULATION_FIXED) === OrderFeeType::CALCULATION_PERCENT) {
                // Tương thích hồ sơ cũ đã gửi trước khi VAT điều chỉnh chuyển sang số tiền.
                $updates['vat_percent'] = $updates['charge_vat'] ? min(max((float) ($state['value'] ?? 0), 0), 100) : 0;
            } else {
                $updates['vat_percent'] = 0;
                $updates['vat_amount'] = $updates['charge_vat'] ? max(0, (float) ($state['value'] ?? 0)) : 0;
            }
        }
        if (isset($changes['shipping']['adjusted'])) {
            $state = $changes['shipping']['adjusted'];
            $updates['charge_shipping_fee'] = (bool) ($state['enabled'] ?? false);
            $updates['shipping_fee'] = $updates['charge_shipping_fee'] ? max(0, (float) ($state['value'] ?? 0)) : 0;
        }
        if (isset($changes['discount']['adjusted'])) {
            $state = $changes['discount']['adjusted'];
            $amount = (bool) ($state['enabled'] ?? false) ? max(0, (float) ($state['value'] ?? 0)) : 0;
            $updates['extra_discount_total'] = $amount;
            $updates['order_discount'] = $amount;
            $updates['order_discount_type'] = 'decrease';
        }
        if (isset($changes['foam_box']['adjusted'])) {
            $state = $changes['foam_box']['adjusted'];
            $updates['charge_foam_box_fee'] = (bool) ($state['enabled'] ?? false);
            $updates['foam_box_price'] = $updates['charge_foam_box_fee'] ? max(0, (float) ($state['value'] ?? 0)) : 0;
        }

        if ($updates !== []) {
            $order->forceFill($updates)->save();
        }
    }

    public function syncCustomFees(Order $order, array $changes, float $baseAmount, ?int $adjustmentId = null): float
    {
        foreach ($changes as $code => $change) {
            if (in_array($code, self::SYSTEM_CODES, true) || (bool) ($change['is_system'] ?? false)) {
                continue;
            }

            $state = (array) ($change['adjusted'] ?? []);
            if (! (bool) ($state['enabled'] ?? false)) {
                $order->additionalFees()->where('fee_code', $code)->delete();
                continue;
            }

            $calculationType = ($change['calculation_type'] ?? 'fixed') === OrderFeeType::CALCULATION_PERCENT
                ? OrderFeeType::CALCULATION_PERCENT
                : OrderFeeType::CALCULATION_FIXED;
            $rate = max(0, (float) ($state['value'] ?? 0));
            $amount = $calculationType === OrderFeeType::CALCULATION_PERCENT
                ? round(max(0, $baseAmount) * min($rate, 100) / 100, 2)
                : round($rate, 2);

            OrderFee::query()->updateOrCreate(
                ['order_id' => $order->id, 'fee_code' => $code],
                [
                    'order_fee_type_id' => $change['fee_type_id'] ?? null,
                    'order_adjustment_id' => $adjustmentId,
                    'fee_name' => $change['name'] ?? $code,
                    'calculation_type' => $calculationType,
                    'direction' => ($change['direction'] ?? 'charge') === OrderFeeType::DIRECTION_DISCOUNT ? OrderFeeType::DIRECTION_DISCOUNT : OrderFeeType::DIRECTION_CHARGE,
                    'rate' => $rate,
                    'base_amount' => round(max(0, $baseAmount), 2),
                    'amount' => $amount,
                ]
            );
        }

        $order->unsetRelation('additionalFees');

        return $this->customNetAmount($order);
    }

    public function customNetAmount(Order $order): float
    {
        return (float) $order->additionalFees()
            ->get()
            ->sum(fn (OrderFee $fee): float => $fee->direction === OrderFeeType::DIRECTION_DISCOUNT ? -1 * (float) $fee->amount : (float) $fee->amount);
    }

    private function customCurrentState(Order $order, OrderFeeType $type): array
    {
        if (! $order->relationLoaded('additionalFees')) {
            $order->load('additionalFees');
        }
        $fee = $order->additionalFees->first(fn (OrderFee $fee): bool => (int) $fee->order_fee_type_id === (int) $type->id || $fee->fee_code === $type->code);

        return [
            'enabled' => (bool) $fee,
            'value' => $fee ? (float) $fee->rate : (float) $type->default_value,
        ];
    }

    private function effectiveCalculationType(OrderFeeType $type): string
    {
        // VAT trong yêu cầu điều chỉnh là một khoản tiền bổ sung trực tiếp.
        // VAT lúc tạo đơn vẫn có thể dùng vat_percent theo quy trình cũ.
        if ($type->code === 'vat') {
            return OrderFeeType::CALCULATION_FIXED;
        }

        return $type->calculation_type === OrderFeeType::CALCULATION_PERCENT
            ? OrderFeeType::CALCULATION_PERCENT
            : OrderFeeType::CALCULATION_FIXED;
    }
}
