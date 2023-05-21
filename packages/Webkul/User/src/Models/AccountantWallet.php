<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\AccountantWallet as AccountantWalletContract;

class AccountantWallet extends Model implements AccountantWalletContract
{
    protected $table = 'accountant_wallet';
    public $timestamps = false;

    protected $fillable = ['accountant_id', 'wallet', 'total_wallet', 'pending_wallet'];

    public function admin()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'accountant_id');
    }
}