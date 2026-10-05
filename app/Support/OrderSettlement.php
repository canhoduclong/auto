<?php

namespace App\Support;

class OrderSettlement
{
    public static function amounts(float $total, float $payments, float $cashRefunds, float $collected, float $unappliedReturns = 0): array
    {
        $paid = max(0.0, round($payments - $cashRefunds, 2));
        $effectivePaid = max($paid, max(0.0, $collected));
        $recognized = max(0.0, round($total - $unappliedReturns, 2));
        $due = max(0.0, round($recognized - $effectivePaid, 2));

        return ['paid' => $paid, 'effective_paid' => $effectivePaid, 'recognized' => $recognized,
            'due' => $due, 'status' => $due <= 0 ? 'paid' : ($effectivePaid > 0 ? 'partially_paid' : 'unpaid')];
    }
}
