<?php

namespace Webkul\Banner\Repositories;

use Carbon\Carbon;
use Webkul\Area\Models\Area;
use Webkul\Banner\Contracts\Banner;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\SubCategory;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Models\Product;

class BannerRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return Banner::class;
    }

    // Listing form customer App
    public function list($section, $request)
    {

        $now=Carbon::now()->toDateString().' 00:00:00';
        $area = request()->header('area');// undefined

        if( $area=='undefined' || !$area  ) {
            $area = Area::where('default', '1')->value('id');
        }

        $query =$this->where(['area_id'=>$area,'section'=>$section])
                     ->where('status' , 1)
                     ->where('start_date', '<=', $now)
                     ->where('end_date', '>=', $now);

        if($query->count() == 0){
            $query =$this->where(['area_id'=>$area,'section'=>$section])
                ->where('status'  , 1)
                ->where('default' , 1);
        }

        $banners = $query->orderBy('position', 'asc')->get();

        return $banners;
    }

}