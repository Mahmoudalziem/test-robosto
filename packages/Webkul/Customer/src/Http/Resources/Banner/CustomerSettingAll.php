<?php

namespace Webkul\Customer\Http\Resources\Banner;
use App\Http\Resources\CustomResourceCollection;

class CustomerSettingAll extends CustomResourceCollection
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

                $data['key']=$setting->key;
                $data['value'] = $setting->value;
                if($setting->value == '0'){
                    $setting->value= false;
                }
                if($setting->value == '1'){
                    $data['value'] = true;
                }


        return $data;
        });
    }

}