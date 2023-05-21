<?php

namespace Webkul\Banner\Models;

use Webkul\Area\Models\Area;

use Webkul\Area\Models\AreaProxy;
use Webkul\Product\Models\Product;

use Webkul\Category\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Category\Models\SubCategory;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Category\Models\CategoryProxy;
use Webkul\Category\Models\SubCategoryProxy;
use Webkul\Banner\Contracts\Banner as BannerContract;

class Banner extends Model implements BannerContract
{

    protected $fillable = [
        'area_id',
        'name',
        'start_date',
        'end_date',
        'position',
        'action_id',
        'actionable_type',
        'section',
        'default',
        'status'
    ];

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function area(){

        return $this->belongsTo(AreaProxy::modelClass());

    }

    public function Category(){

        return $this->belongsTo(CategoryProxy::modelClass(),'action_id','id');
    }
    public function SubCategory(){

        return $this->belongsTo(SubCategoryProxy::modelClass(),'action_id','id');
    }

    public function Product(){

        return $this->belongsTo(ProductProxy::modelClass(),'action_id','id');
    }


    public function imageArUrl()
    {
        if (! $this->image_ar) return;
        return Storage::url($this->image_ar);
    }

    public function imageEnUrl()
    {
        if (! $this->image_en) return;
        return Storage::url($this->image_en);
    }
}