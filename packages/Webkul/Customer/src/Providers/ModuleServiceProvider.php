<?php

namespace Webkul\Customer\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Customer\Models\Avatar::class,
        \Webkul\Customer\Models\Customer::class,
        \Webkul\Customer\Models\CustomerLoginOtp::class,
        \Webkul\Customer\Models\CustomerAddress::class,
        \Webkul\Customer\Models\Wishlist::class,
        \Webkul\Customer\Models\CustomerDeviceToken::class,
        \Webkul\Customer\Models\CustomerSetting::class,
        \Webkul\Customer\Models\CustomerInvitation::class,
        \Webkul\Customer\Models\VapulusCard::class,
        \Webkul\Customer\Models\CustomerNote::class, 
        \Webkul\Customer\Models\PaymobCard::class,
        \Webkul\Customer\Models\PaymobPendingCard::class,
        \Webkul\Customer\Models\CustomerPayment::class,
        \Webkul\Customer\Models\WalletNote::class,
        \Webkul\Customer\Models\WalletCustomerReason::class,
        \Webkul\Customer\Models\WalletCustomerItem::class,
        \Webkul\Customer\Models\CustomerDevice::class,
        \Webkul\Customer\Models\BuyNowPayLater::class,
        \Webkul\Customer\Models\CustomerFavoriteProducts::class,
    ];
}
