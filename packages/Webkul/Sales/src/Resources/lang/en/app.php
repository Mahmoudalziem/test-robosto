<?php

return [
    'cannotCreateManyOrderAtTime'   =>  'Sorry, you cannot request more than order within :seconds seconds',
    'itemsNotAvailable'   =>  'Sorry, requested items not available yet',
    'shcedulTimeNotValid'   =>  'Sorry, you cannot schedul order at this time',
    'orderCannotUpdate'     => 'Sorry, the order cannot be updated',
    'cardIdRequired' => 'Sorry, Online Payment method Not Allowed, with no card',

    'pending_order_status'  =>  'Pending',
    'waiting_customer_order_status'  =>  'ًWaiting Customer Response',
    'preparing_order_status'  =>  'Preparing',
    'ready_to_pickup_order_status'  =>  'Ready to pickup',
    'on_the_way_order_status'  =>  'On the way',
    'at_place_order_status'  =>  'At Place',
    'delivered_order_status'  =>  'Delivered',
    'cancelled_order_status'  =>  'Cancelled',
    'scheduled_order_status'  =>  'Scheduled',
    'returned_order_status'  =>  'Returned',
    
    'refund_to_customer'  =>  "Order #:order Cancelled, :amount EGP has been refunded to your wallet",

    'address_not_match_the_customer'    =>  "This address doesn't belongs to you",
    
    'success_placed_order'    =>  "Order has been placed successfully",    
    'success_sheduled_order_at_closed_hours'    =>  "Order has been scheduled at :scheduled_at successfully",    
    
    'driverCannotReceiveOrdersNow'  => 'The Driver Cannot Receive Orders Now',
    'driverCannotAssignedToSchudeledOrder'  => 'Cannot Assign Driver to the scheduled order',
    'collectorCannotReceiveOrder'   => 'The Collector Cannot Receive This Order Now',
    'collectorDoesntBelongsToWarehouse'   => 'The Collector Does not belongs to the order warehouse',
    'orderStatusCannotUpdateNow'    => 'The Current Order Status Cannot Be Modified with this action',
];
