<?php

namespace App\Enums;

enum OrderStatus: string {
    
    case Pending = 'pending';
    case LeaderConfirmed = 'leader_confirmed';
    case AccountingPlanned = 'accounting_planned';
    case ManagerConfirmed = 'manager_confirmed';
    case WarehouseConfirmed = 'warehouse_confirmed';
    case FactoryConfirmed = 'factory_confirmed';
    case Shipping = 'shipping'; 
    case Returned = 'returned';


    case Draft = 'draft';
    case PendingManagerApproval = 'pending_manager_approval';
    case PendingDirectorApproval = 'pending_director_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';

    case Preparing = 'preparing';
    case Packing = 'packing';
    case PackedWaitingPickup = 'packed_waiting_pickup';
    case OutOfStock = 'out_of_stock';

    case PickedUp = 'picked_up';
    case Delivering = 'delivering';
    case Delivered = 'delivered';

    case Unpaid = 'unpaid';
    case Completed = 'completed';

    case PartiallyReturned = 'partially_returned';
    case FullyReturned = 'fully_returned';
}
