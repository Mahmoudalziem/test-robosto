<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\AreaManagerWallet as AreaManagerWalletContract;

class AreaManagerWallet extends Model implements AreaManagerWalletContract
{
    protected $table = 'area_manager_wallet';
    public $timestamps = false;
    
    protected $fillable = ['area_manager_id', 'wallet', 'total_wallet', 'pending_wallet'];

    public function admin()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'area_manager_id');
    }
}