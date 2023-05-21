<?php

namespace Webkul\Admin\Http\Resources\Banner;

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

        return  $this->collection->map(function ($banner) {

            return [
                'id'            => $banner->id,
                'area'         => $banner->area->name,
                'name'         => $banner->name,
                'banner_type'         => $banner->actionable_type,
                'action_id'         => $banner->action_id,
                'section'         => $banner->section,
                'start_date'         => $banner->start_date,
                'end_date'         => $banner->end_date,
                'position'         => $banner->position,
                'status'         => $banner->status,
                'default'         => $banner->default,
                'image_en'         => $banner->imageEnUrl(),
                'image_ar'         => $banner->imageArUrl(),
                'created_at'    => $banner->created_at  ,
            ];
        });
    }

}