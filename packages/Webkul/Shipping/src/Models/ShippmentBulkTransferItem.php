<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Shipping\Contracts\ShippmentBulkTransferItem as ShippmentBulkTransferItemContract;

class ShippmentBulkTransferItem extends Model implements ShippmentBulkTransferItemContract
{
    protected $table = 'shippment_bulk_transfer_item';
    protected $fillable = ['transfer_id','shippment_id'];

    public function transfer()
    {
        return $this->belongsTo(ShippmentBulkTransferProxy::modelClass(), 'transfer_id');
    }

    public function shippment()
    {
        return $this->belongsTo(ShippmentProxy::modelClass(),'shippment_id');
    }   
}