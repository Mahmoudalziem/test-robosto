<?php
namespace App\Enums;

class TrackingUserEvents
{
    public const INITIATE_CHECKOUT      = 'InitiateCheckout';
    public const ADD_TO_CART            = 'AddToCart';
    public const SEARCH                 = 'Search';
    public const COMPLETE_REGISTRATION  = 'CompleteRegistration';
    public const ADD_PAYMENT_INFO       = 'AddPaymentInfo';
    public const PURCHASE               = 'Purchase';
}