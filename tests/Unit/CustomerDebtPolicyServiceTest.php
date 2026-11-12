<?php

namespace Tests\Unit;

use App\Services\CustomerDebtPolicyService;
use PHPUnit\Framework\TestCase;

class CustomerDebtPolicyServiceTest extends TestCase
{
    private function sale(string $date, int $amount = 1000, int $gift = 0): array
    {
        return ['date' => $date, 'amount' => $amount, 'gift' => $gift, 'type' => 'sale'];
    }

    public function test_adjustments_change_debt_without_creating_a_bill_or_delivery(): void
    {
        $service = new CustomerDebtPolicyService;
        $result = $service->evaluate([
            $this->sale('2026-10-01'),
            ['date' => '2026-11-12', 'type' => 'adjustment', 'amount' => 500, 'adjustment_id' => 1],
        ], [], '2026-11-12');
        self::assertSame(1500, $result['balance']);
        self::assertSame(1, $result['unpaid_orders']);
        self::assertSame('2026-10-01', $result['latest_delivery_date']);
        self::assertSame('2026-10-02', $result['due_date']);
        $result = $service->evaluate([
            $this->sale('2026-09-01'), $this->sale('2026-11-11'),
            ['date' => '2026-11-12', 'type' => 'adjustment', 'amount' => -1500, 'adjustment_id' => 1],
        ], [], '2026-11-12');
        self::assertSame(500, $result['balance']);
        self::assertSame(1, $result['unpaid_orders']);
        self::assertSame('2026-11-11', $result['oldest_date']);
    }

    public function test_payments_clear_oldest_sales_and_partial_sales_still_count(): void
    {
        $service = new CustomerDebtPolicyService;
        $alert = $service->evaluate([
            $this->sale('2026-09-01', 1000, 200),
            $this->sale('2026-10-30', 1000),
            $this->sale('2026-10-30', 500),
            $this->sale('2026-11-10', 1000),
            ['date' => '2026-11-12', 'type' => 'payment', 'amount' => 1000],
        ], ['allowed_orders' => 2, 'credit_limit' => 2300, 'due_days' => 7], '2026-11-12');
        self::assertSame(2300, $alert['balance']);
        self::assertSame(2, $alert['unpaid_orders']);
        self::assertSame('2026-10-30', $alert['oldest_date']);
        self::assertSame(13, $alert['age_days']);
        self::assertSame(0, $alert['overdue_days']);
        self::assertTrue($alert['remind']);
        self::assertNull($alert['risk']);
    }

    public function test_risk_boundaries_follow_age_of_outstanding_debt(): void
    {
        $service = new CustomerDebtPolicyService;
        foreach ([14 => null, 15 => 'medium', 30 => 'medium', 31 => 'high', 60 => 'high', 61 => 'very_high'] as $age => $risk) {
            $date = \Carbon\CarbonImmutable::parse('2026-11-12')->subDays($age)->toDateString();
            $result = $service->evaluate([$this->sale($date)], [], '2026-11-12');
            self::assertSame($risk, $result['risk']);
        }
    }

    public function test_paid_gifted_and_prepaid_balances_do_not_warn(): void
    {
        $service = new CustomerDebtPolicyService;
        foreach ([800, 1000] as $paid) {
            $result = $service->evaluate([$this->sale('2026-01-01', 1000, 200), ['date' => '2026-02-01', 'type' => 'payment', 'amount' => $paid]], ['allowed_orders' => 0, 'credit_limit' => 0, 'due_days' => 0], '2026-11-12');
            self::assertSame(0, $result['unpaid_orders']);
            self::assertFalse($result['overdue']);
            self::assertFalse($result['remind']);
            self::assertNull($result['risk']);
        }
        self::assertSame(0, $service->evaluate([$this->sale('2026-01-01', 1000, 1000)], [], '2026-11-12')['balance']);
    }

    public function test_due_date_reminder_and_overdue_start_on_the_correct_days(): void
    {
        $service = new CustomerDebtPolicyService;
        foreach ([3 => [false, false], 4 => [true, false], 7 => [true, false], 8 => [true, true]] as $age => $expected) {
            $date = \Carbon\CarbonImmutable::parse('2026-11-12')->subDays($age)->toDateString();
            $result = $service->evaluate([$this->sale($date)], ['allowed_orders' => 99, 'credit_limit' => 1000000, 'due_days' => 7], '2026-11-12');
            self::assertSame($expected[0], $result['remind']);
            self::assertSame($expected[1], $result['overdue']);
        }
    }

    public function test_default_terms_are_zero_bills_zero_credit_and_one_day_after_delivery(): void
    {
        $service = new CustomerDebtPolicyService;
        $policy = $service->effective(['customer_id' => 1], []);
        self::assertSame(['allowed_orders' => 0, 'credit_limit' => 0, 'due_days' => 1], array_intersect_key($policy, $service->defaults()));
        $result = $service->evaluate([$this->sale('2026-11-10')], [], '2026-11-12');
        self::assertTrue($result['remind']);
        self::assertSame('2026-11-11', $result['due_date']);
        self::assertSame(1, $result['overdue_days']);
        $onDueDay = $service->evaluate([$this->sale('2026-11-11')], [], '2026-11-12');
        self::assertFalse($onDueDay['overdue']);
    }

    public function test_custom_terms_are_preserved_and_old_unset_values_use_defaults(): void
    {
        $service = new CustomerDebtPolicyService;
        $custom = ['allowed_orders' => 3, 'credit_limit' => 10000000, 'due_days' => 7];
        self::assertSame($custom, array_intersect_key($service->effective(['customer_id' => 1], ['customer_1' => $custom]), $service->defaults()));
        self::assertSame($service->defaults(), array_intersect_key($service->effective(['customer_id' => 1], ['customer_1' => ['allowed_orders' => null, 'credit_limit' => null, 'due_days' => null]]), $service->defaults()));
        self::assertSame(['allowed_orders' => 0, 'credit_limit' => 0, 'due_days' => 0], array_intersect_key($service->effective(['customer_id' => 1], ['customer_1' => ['allowed_orders' => 0, 'credit_limit' => 0, 'due_days' => 0]]), $service->defaults()));
    }

    public function test_future_sales_and_receipts_do_not_change_todays_warning(): void
    {
        $result = (new CustomerDebtPolicyService)->evaluate([$this->sale('2026-11-13'), ['date' => '2026-11-14', 'type' => 'payment', 'amount' => 1000]], ['due_days' => 0], '2026-11-12');
        self::assertSame(0, $result['balance']);
        self::assertFalse($result['remind']);
    }

    public function test_due_days_start_at_latest_delivery_while_risk_keeps_fifo_age(): void
    {
        $result = (new CustomerDebtPolicyService)->evaluate([
            $this->sale('2026-10-01', 1000), $this->sale('2026-11-10', 300),
            ['date' => '2026-11-12', 'type' => 'payment', 'amount' => 100],
        ], ['due_days' => 1], '2026-11-12');
        self::assertSame('2026-11-11', $result['due_date']);
        self::assertSame(1, $result['overdue_days']);
        self::assertSame(42, $result['age_days']);
        self::assertSame('high', $result['risk']);
    }

    public function test_legacy_saved_policies_are_locked_until_admin_reopens(): void
    {
        $service = new CustomerDebtPolicyService;
        self::assertFalse($service->isLocked([]));
        self::assertTrue($service->isLocked(['updated_at' => '2026-11-12T09:00:00+07:00']));
        self::assertFalse($service->isLocked(['updated_at' => '2026-11-12T09:00:00+07:00', 'staff_edit_locked' => false]));
    }
}
