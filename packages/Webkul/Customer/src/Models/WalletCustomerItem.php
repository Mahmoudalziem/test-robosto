<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\WalletCustomerItem as WalletCustomerItemContract;
use Webkul\Customer\Models\WalletNoteProxy;
class WalletCustomerItem extends Model implements WalletCustomerItemContract {

    protected $fillable = ['wallet_note_id', 'order_id', 'product_id', 'qty', 'price'];

    public function walletNote() {
        return $this->belongsTo(WalletNoteProxy::modelClass());
    }

}
