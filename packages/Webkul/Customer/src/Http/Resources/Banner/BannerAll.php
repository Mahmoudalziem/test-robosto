<?php

namespace Webkul\Customer\Http\Resources\Banner;
use App\Http\Resources\CustomResourceCollection;

class BannerAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        $lang = $request->header('lang');

        return  $this->collection->map(function ($banner) use ($lang) {
            $additional = [];
            if($banner->actionable_type=="SubCategory"){
                $additional = ['category' => $banner->subCategory ? $banner->subCategory->parentCategories->first()->id : null];
            }

            return [

                'id'            => $banner->id,
                'area'         => $banner->area->name,
                'name'         => $banner->name,
                'action_type'  => $banner->actionable_type,
                'action_id'         => $banner->action_id,
                'section'         => $banner->section,
                'start_date'         => $banner->start_date,
                'end_date'         => $banner->end_date,
                'status'         => $banner->status,
                'default'         => $banner->default,
                'image'         => ($lang == 'ar')?$banner->imageArUrl():$banner->imageEnUrl(),
                'created_at'    => $banner->created_at  ,
            ]+$additional;
        });
    }

}