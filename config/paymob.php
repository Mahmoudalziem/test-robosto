<?php

return [
    /*
      |--------------------------------------------------------------------------
      | Debug option
      |--------------------------------------------------------------------------
      | Accept boolean value , and toggle between the production endpoint and sandbox
     */

    'debug' => env('PAYMOB_DEBUG', true),
    'test' => env('PAYMOB_TEST', false),
    /*
      |--------------------------------------------------------------------------
      | Fawry Keys
      |--------------------------------------------------------------------------
      |
      | The Fawry publishable key and secret key give you access to Fawry's
      | API.
     */
    'api_key' => env('PAYMOB_API_KEY', "ZXlKaGJHY2lPaUpJVXpVeE1pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnVZVzFsSWpvaU1UWXlNVE16TmpBeU15NHpPREU0TmpZaUxDSmpiR0Z6Y3lJNklrMWxjbU5vWVc1MElpd2ljSEp2Wm1sc1pWOXdheUk2TmpZMk5EZDkuTGJHY2tCYmhwejZneXFFYzN4OHprcjdsRVE1ZWJuYUJsNXBsY3BEREdRX1lIS3lRcW9ZZVVBcGIxa240UGFGeWkybXFuMEdxMTk2a2dad2ZhSTJucFE="),
    'hmac' => '9AF416D0DD2BBB2AACBC33EEBF3BBC87',
    'integeration_id' => env('PAYMOB_INTEGERATION_ID', "323642"),  
    'integeration_moto_id' => env('PAYMOB_INTEGERATION_MOTO_ID', "323643"),  
    'iframe_id' => env('PAYMOB_IFRAME_ID', "166076"),
    'amount_base_rate' => env('PAYMOB_BASE_RATE', 100), // 100 CENTS
 
    'url' => [
        'iframe' => 'https://accept.paymobsolutions.com/api/acceptance/iframes/',
        'token' => 'https://accept.paymobsolutions.com/api/auth/tokens',
        'order' => 'https://accept.paymobsolutions.com/api/ecommerce/orders',
        'payment_key' => 'https://accept.paymobsolutions.com/api/acceptance/payment_keys',
        'hmac' => 'https://accept.paymobsolutions.com/api/acceptance/transactions',
        'card_token'=>'https://accept.paymobsolutions.com/api/acceptance/payments/pay',
        'refund' => 'https://accept.paymobsolutions.com/api/acceptance/void_refund/refund',        
        'transaction' => 'https://accept.paymob.com/api/acceptance/transactions/',
        'inquiry_order' => "https://accept.paymobsolutions.com/api/ecommerce/orders/transaction_inquiry",
    ],
];
