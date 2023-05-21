<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\WalletCustomerReason as WalletCustomerReasonContract;

class WalletCustomerReason extends Model implements WalletCustomerReasonContract {

    
    public const TYPE_AMOUNT = 'amount';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_NONE = 'none';
 
    

    protected $fillable = ['reason', 'type', 'is_added', 'is_reduced'];

}
