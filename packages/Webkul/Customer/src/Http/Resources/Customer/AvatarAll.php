<?php

namespace Webkul\Customer\Http\Resources\Customer;
use App\Http\Resources\CustomResourceCollection;

class AvatarAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {


        return  $this->collection->map(function ($avatar)  {

            return [
                'id'            => $avatar->id,
                'image'         =>  $avatar->image ,
                'image_url'         =>  $avatar->image_Url ,
                'gender'         => $avatar->gender,
            ] ;
        });
    }

}