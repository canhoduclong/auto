<?php

namespace App\Enums;

enum ReturnStatus: string {
    case Requested = 'requested';
    case Received = 'received';
    case RefundPending = 'refund_pending';
    case Refunded = 'refunded';
    case DebtDeducted = 'debt_deducted';
    case Exchanged = 'exchanged';
}
