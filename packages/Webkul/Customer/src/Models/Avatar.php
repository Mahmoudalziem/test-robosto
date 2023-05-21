<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\ProductProxy;
use Webkul\Customer\Contracts\Avatar as AvatarContract;

class Avatar extends Model implements AvatarContract
{
    protected $fillable = ['image', 'gender'];
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        return Storage::url($this->image);
    }
}
