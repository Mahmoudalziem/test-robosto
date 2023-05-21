<?php

namespace Webkul\Customer\Http\Resources\Banner;
use App\Http\Resources\CustomResourceCollection;

class AppInfoAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {

        return  $this->collection->map(function ($setting)   {
            if($setting->group == 'social'){
                $data['social']['key']=$setting->key;
                $data['social']['value']=$setting->value;
            }else{
                $data['key']   = $setting->key;
                $data['value'] = $setting->value;
            }
        return $data;
        });
    }

}