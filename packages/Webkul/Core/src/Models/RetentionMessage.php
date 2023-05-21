<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Core\Contracts\RetentionMessage as RetentionMessageContract;

class RetentionMessage extends Model implements RetentionMessageContract
{
    protected $table = 'retention_messages';

    protected $fillable = ['no_of_orders', 'no_of_days', 'tag_id', 'status'];

    public function tag()
    {
        return $this->belongsTo(TagProxy::modelClass());
    }

    public function retentionedCustomers()
    {
        return $this->hasMany(RetentionCustomerProxy::modelClass(), 'retention_id');
    }

    /**
     * Scope a query to only include active products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 1);
    }
}