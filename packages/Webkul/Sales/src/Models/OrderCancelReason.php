<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\OrderCancelReason as OrderCancelReasonContract;

class OrderCancelReason extends Model implements OrderCancelReasonContract
{
    protected $fillable = ['order_id','reason'];
}