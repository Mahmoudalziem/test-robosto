<?php

namespace Webkul\Customer\Models;

use Webkul\User\Models\AdminProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Customer\Contracts\WalletNote as WalletNoteContract;
use Webkul\Customer\Models\WalletCustomerItemProxy;
use Webkul\Customer\Models\WalletCustomerReasonProxy;

class WalletNote extends Model implements WalletNoteContract {

    public const ADD_MONEY = 'plus';
    public const SUBTRACT_MONEY = 'minus';

    protected $table = 'wallet_notes';
    protected $fillable = ['customer_id', 'admin_id', 'order_id', 'text', 'reason_id', 'type', 'wallet_before', 'amount', 'status'];

    public function admin() {
        return $this->belongsTo(AdminProxy::modelClass());
    }

    /**
     * Get the customer record associated with the order.
     */
    public function customer() {
        return $this->belongsTo(CustomerProxy::modelClass());
    }

    public function items() {
        return $this->hasMany(WalletCustomerItemProxy::modelClass());
    }

    public function reason() {
        return $this->belongsTo(WalletCustomerReasonProxy::modelClass());
    }

}
