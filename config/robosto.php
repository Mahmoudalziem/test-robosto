<?php
return [
    'SHIPPING_PRODUCT' => env('SHIPPING_PRODUCT', '5165'),
    'SHIPPING_PORTAL_URL' => env('SHIPPING_PORTAL_URL', 'http://shipping.robostodelivery.com'),
    'GOOGLE_API_KEY' => env('GOOGLE_API_KEY', 'AIzaSyAU6MnKfh4iKRX7aSkbHAkXpRV1NPmuEVM'),
    'DELIVERY_CHARGS' => env('DELIVERY_CHARGS', 10),
    'DEFAULT_TAX' => env('DEFAULT_TAX', 0),
    'PENDING_ORDER_BUFFER' => env('PENDING_ORDER_BUFFER', 25), // in minutes
    'ACTIVE_ORDER_BUFFER' => env('ACTIVE_ORDER_BUFFER', 120),
    'QAUNTITY_PREPARING_TIME' => env('ACTIVE_ORDER_BUFFER', 10),
    'ORDER_PRICE_BUFFER' => env('ORDER_PRICE_BUFFER', 0),
    'INVITE_CODE_GIFT' => env('INVITE_CODE_GIFT', 20),
    'ORDER_INVITE_CODE_GIFT' => env('ORDER_INVITE_CODE_GIFT', 15),
    'REFERRAL_CODE_USAGE_COUNT' => env('REFERRAL_CODE_USAGE_COUNT', 10),
    'MANY_ORDER_WITHIN' => env('MANY_ORDER_WITHIN', 30),
    'CALLCENTER_DISABLE_BUTTON' => env('CALLCENTER_DISABLE_BUTTON', 20),
    'ORDER_SCHEDULE_TIME_BUFFER' => env('ORDER_SCHEDULE_TIME_BUFFER', 1), // in hours
    // SMS
    'VICTORY_LINK_USER' => env('VICTORY_LINK_USER', 'BusinessValley'),
    'VICTORY_LINK_PASSWORD' => env('VICTORY_LINK_PASSWORD', '9N9m9t9VXF'),
    // Payment
    'VAPULUS_APP_ID' => env('VAPULUS_APP_ID', 'BusinessValley'),
    'VAPULUS_PASSWORD' => env('VAPULUS_PASSWORD', 'BusinessValley'),
    'VAPULUS_HASH_SECRET' => env('VAPULUS_HASH_SECRET', 'C0DF9A7B3819968807A9D4E48D0E65C6'),
    'PAYMENT_MAIL' => env('PAYMENT_MAIL', 'payment@robostodelivery.com'),
    // Commands
    'SCHEDULED_ORDER_COMMAND' => env('SCHEDULED_ORDER_COMMAND', 60),
    'CALL_AREA_MANAGER_DURATION' => env('CALL_AREA_MANAGER_DURATION', 600),
    'CALL_OPERATION_MANAGER_DURATION' => env('CALL_OPERATION_MANAGER_DURATION', 900),
    'MINIMUM_ORDER_AMOUNT' => env('MINIMUM_ORDER_AMOUNT', 100),
    'FREE_SHIPPING_COUPON' => env('FREE_SHIPPING_COUPON', 'Free-Delivery'),
    'HASHTAGS' => [
        'name',
        'email',
        'phone'
    ],
    'EXCLUDED_CATEGORIES' => [16],
    'EXTERA_PROMOTOION_RULES' => [
        [
            'promo_code_id' => 121,
            'excluded_from_categories_offer' => false,
            'max_device_count' => 1,
        ]
    ],
    'PROMOTOION_CASH_BACK' => [
        [
            'promo_code_id' => 138,
            'amount' => 100
        ],
        [
            'promo_code_id' => 142,
            'amount' => 50
        ],
        [
            'promo_code_id' => 143,
            'amount' => 50
        ],
        [
            'promo_code_id' => 152,
            'amount' => 100
        ],
        [
            'promo_code_id' => 161,
            'amount' => 100
        ],
        [
            'promo_code_id' => 173,
            'amount' => 50
        ]
    ],
    'BNPL_INTEREST' => env('BNPL_INTEREST', 0.02),
    'BNPL_RELEASE_AFTER' => env('BNPL_RELEASE_AFTER', 10),
    'BNPL_AFTER_MONTH' => env('BNPL_AFTER_MONTH', 2),
    'BNPL_MINIMUM_ORDERS' => env('BNPL_AFTER_MONTH', 10),
    'DELIVERY_TIME' => env('DELIVERY_TIME', 60), // in minutes
    'EXPECTED_TIME_FOR_PREPARING_ORDER' => env('EXPECTED_TIME_FOR_PREPARING_ORDER', 5), // in minutes
    'ROBOSTO_PHONE' => env('ROBOSTO_PHONE', "0225878180"),
    'DRIVER_SUPERVISOR_RATE_PER' => env('DRIVER_SUPERVISOR_RATE_PER', 5),
    'DRIVER_BACK_BONUS' => env('DRIVER_BACK_BONUS', 15),
    'DRIVER_MAX_BACK_DISTANCE' => env('DRIVER_MAX_BACK_DISTANCE', 15),
    'DRIVER_WORK_HOURS' => env('DRIVER_WORK_HOURS', 8.5),
    'WHITE_FRIDAY_PRODUCTS' => [
    ],
    'LIMITED_PRODUCTS_QUANTITY' => [],
    'CALL_CUSTOMER_WAITING_ORDER' => env('CALL_CUSTOMER_WAITING_ORDER', 3),
    'CALL_DRIVER_ORDER_AT_PLACE' => env('CALL_DRIVER_ORDER_AT_PLACE', 10),
    'FB_PIXEL_TOKEN' => env('FB_PIXEL_TOKEN', 'EAAfXqazU4NkBAGZAud3nK2aVax7qeTtDwBGiWUv3mztyzJFZCMvn4raAIIIUrK5GQByytcKeHXgXEKIIuV6tms8QuzRaX6HDw0oZC81Dv2oCyONiz3HXFcVrm3RZAuFcUAYKZAGlIjohDiu9wUS91gjTkEbzwXRyznAtNExvoskMX2vETPNGv9ZBsZARSU34ZAoZD'),
    'FB_PIXEL_ID' => env('FB_PIXEL_ID', '135649111729466'),
    'ENABLE_USER_TRACK' => env('ENABLE_USER_TRACK', false),
    'STOCK_WAREHOUSE' => ['warehouse_id' => 10, 'area_id' => 7]
];
