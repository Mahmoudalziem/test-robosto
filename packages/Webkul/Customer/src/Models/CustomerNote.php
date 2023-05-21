<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\CustomerNote as CustomerNoteContract;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\User\Models\AdminProxy;

class CustomerNote extends Model implements CustomerNoteContract
{

    protected $table = 'customer_notes';

    protected $fillable = ['customer_id', 'admin_id', 'text'];

    
    public function admin() {
        return $this->belongsTo( AdminProxy::modelClass());
    }  
    
    /**
     * Get the customer record associated with the order.
     */
    public function customer()
    {
        return $this->belongsTo( CustomerProxy::modelClass());
    }  
}