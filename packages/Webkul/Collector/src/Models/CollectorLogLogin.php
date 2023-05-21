<?php

namespace Webkul\Collector\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Collector\Contracts\CollectorLogLogin as CollectorLogLoginContract;

class CollectorLogLogin extends Model implements CollectorLogLoginContract
{
    protected $fillable = ['collector_id','action'];
}