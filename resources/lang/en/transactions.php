<?php

return [
    'titles' => [
        'index' => 'Transaction List',
        'create' => 'Create Transaction',
    ],
    'labels' => [
        'id' => 'ID',
        'order' => 'Order',
        'customer' => 'Customer',
        'amount' => 'Amount',
        'type' => 'Type',
        'method' => 'Method',
        'note' => 'Note',
        'created_at' => 'Created At',
        'total' => 'Total',
        'paid' => 'Paid',
        'remaining' => 'Remaining',
    ],
    'buttons' => [
        'add' => 'Add Transaction',
        'save' => 'Save Transaction',
        'select_customer' => 'Select customer',
    ],
    'types' => [
        'payment' => 'Payment',
        'refund' => 'Refund',
        'fee' => 'Fee',
        'extra_income' => 'Extra income',
        'extra_expense' => 'Extra expense',
    ],
    'placeholders' => [
        'order_optional' => 'Order (optional)',
        'customer_optional' => 'Customer (optional)',
        'no_link' => '-- No link --',
        'choose_customer' => 'Choose customer',
        'pay_full' => 'Pay full amount',
        'pay_full_short' => 'Full payment',
    ],
    'messages' => [
        'created' => 'Transaction has been recorded.',
    ],
];
