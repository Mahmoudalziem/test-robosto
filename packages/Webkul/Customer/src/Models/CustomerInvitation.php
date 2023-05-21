<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Customer\Contracts\CustomerInvitation as CustomerInvitationContract;

class CustomerInvitation extends Model implements CustomerInvitationContract
{
    protected $table = 'customer_invitations';

    protected $fillable = [
        'customer_id',
        'inviter_id',
        'order_id',
        'wallet'
    ];

    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass());
    }

    public function inviter()
    {
        return $this->belongsTo(CustomerProxy::modelClass(),'inviter_id');
    }
}