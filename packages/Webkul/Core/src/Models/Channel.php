<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Category\Models\CategoryProxy;
use Webkul\Inventory\Models\InventorySourceProxy;
use Webkul\Core\Contracts\Channel as ChannelContract;

class Channel extends Model implements ChannelContract
{

    public const CALL_CENTER    = 1;
    public const MOBILE_APP     = 2;
    public const SHIPPING_SYSTEM     = 3;

    protected $fillable = [
        'name',
    ];
}