<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\OrderNote as OrderNoteContract;

class OrderNote extends Model implements OrderNoteContract {

    protected $fillable = ['order_id', 'admin_id','customer_id', 'text'];

    /**
     * Get the order record associated with the order item.
     */
    public function order() {
        return $this->belongsTo(OrderProxy::modelClass());
    }
    
    public function admin() {
        return $this->belongsTo(\Webkul\User\Models\AdminProxy::modelClass());
    }  
    
    /**
     * Get the customer record associated with the order.
     */
    public function customer()
    {
        return $this->belongsTo(\Webkul\Customer\Models\CustomerProxy::modelClass());
    }    

}
