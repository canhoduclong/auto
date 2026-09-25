<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case NotShipped = 'not_shipped';
    case Shipping = 'shipping';
    case Delivered = 'delivered';
    case Returned = 'returned';
}
