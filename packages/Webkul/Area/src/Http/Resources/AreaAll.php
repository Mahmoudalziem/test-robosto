<?php

namespace Webkul\Area\Http\Resources;


use App\Http\Resources\CustomResourceCollection;
use Webkul\Customer\Repositories\CustomerAddressRepository;

class AreaAll extends CustomResourceCollection
{

    public function toArray($request)
    {
        return  $this->collection->map(function ($area) {

            return [
                'id' => $area->id ,
                'name' =>$area->name ,
                'location_type' => 'area' ,
                'created_at' => $area->created_at ,
                'updated_at' => $area->updated_at ,
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