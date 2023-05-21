<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Contracts\Tag as TagContract;
use Webkul\Promotion\Models\PromotionProxy;

class Tag extends Model implements TagContract
{
    public const NEW_USER    = 1; // new-user
    public const FIRST_ORDER    = 2; // first-order
    public const SECOND_ORDER    = 3; // second-order
    public const ALL_USERS    = 4; // all-users

    protected $fillable = ['name', 'send_sms', 'one_order'];
    
    public function customers()
    {
        return $this->belongsToMany(CustomerProxy::modelClass(), 'customer_tags')->withTimestamps();
    }

    public function promotions()
    {
        return $this->belongsToMany(PromotionProxy::modelClass(), 'promotion_tags', 'tag_id')->withTimestamps();
    }
}