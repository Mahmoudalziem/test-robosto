<?php

namespace Webkul\Area\Http\Resources;


use App\Http\Resources\CustomResourceCollection;
use Webkul\Customer\Repositories\CustomerAddressRepository;

class AreaAddressAll extends CustomResourceCollection
{

    public function toArray($request)
    {
        return  $this->collection->map(function ($customerAddress) {

            return [
                'id' => $customerAddress->id ,
                'name' =>$customerAddress->name ,
                'address' =>$customerAddress->address ,
                'building_no' =>$customerAddress->building_no ,
                'flat_no' =>$customerAddress->flat_no ,
                'apartment_no' =>$customerAddress->apartment_no ,
                'phone' =>$customerAddress->phone ,
                'latitude' =>$customerAddress->latitude ,
                'longitude' =>$customerAddress->longitude ,
                'area_id' => $customerAddress->area->id ,
                'area_name' =>$customerAddress->area->name ,
                'location_type' => 'address' ,
                'created_at' => $customerAddress->area->created_at ,
                'updated_at' => $customerAddress->area->updated_at ,
            ];
        });

    }

    public function get(CustomerAddressRepository $customerAddressRepository)
    {

        // if customer logged in
        // get area id from address table
        if(auth()->guard("customer")->check()){
            // get default area from shipped address of the last order has been placed.

            // if not then get area id from address table
            $customerAddress= $customerAddressRepository->with('area')->findWhere(['customer_id'=>auth()->guard("customer")->user()->id]);
            $data=new AreaAddressAll($customerAddress);
            return $this->responseSuccess($data);

        }else{ // get area from the default area table
            return $this->responseSuccess($this->areaRepository->all());
        }




    }

}