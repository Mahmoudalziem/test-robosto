<?php

namespace Webkul\Customer\Http\Controllers;

use Auth;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Webkul\Area\Models\Area;
use Illuminate\Http\Response;
use Webkul\Sales\Models\Order;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Rules\VatIdRule;
use Webkul\Core\Services\CheckPointInArea;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Core\Services\Payment\Vapulus\VapulusService;
use Webkul\Customer\Http\Requests\CustomerAddressRequest;
use Webkul\Customer\Http\Resources\Customer\AddressSingle;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Customer\Http\Requests\CustomerAddressUpdateRequest;

class AddressController extends BackendBaseController
{
    /**
     * Contains route related configuration
     *
     * @var mixed
     */

    protected $customer;
    /**
     * CustomerAddressRepository object
     *
     * @var CustomerAddressRepository
     */
    protected $customerAddressRepository;

    /**
     * Create a new controller instance.
     *
     * @param CustomerAddressRepository $customerAddressRepository
     * @return void
     */
    public function __construct(CustomerAddressRepository $customerAddressRepository)
    {
        $this->customerAddressRepository = $customerAddressRepository;
        $this->customer = auth()->guard('customer')->user();
    }

    public function list(){
        
        return $this->responseSuccess($this->customerAddressRepository->where('customer_id', $this->customer->id)->covered()->latest()->get() );
    }

    public function show(  $customerAddress){
        return $this->responseSuccess( $this->customerAddressRepository->findOrFail($customerAddress) );
    }



    public function add(CustomerAddressRequest $request)
    {
        $data = $request->only('area_id', 'icon_id', 'name','address','building_no','floor_no','apartment_no','landmark','location','phone' ,'is_default');
        
        $data['latitude']=$data['location']['lat'];
        $data['longitude']=$data['location']['lng'];
        $data['customer_id']=$this->customer->id;

        if (!isset($data['phone']) || empty($data['phone'])) {
            $data['phone'] = $this->customer->phone;
        }
        
        // First Check that given location is covered by Robosto
        $checkLocationCovered = (new CheckPointInArea($data['location']))->check();
        if (!$checkLocationCovered) {
            // Save the address with not covered status
            $data['covered'] = '0';
            $this->customerAddressRepository->create($data);

            return $this->responseError(422, __('core::app.areaNotCovered'));
        }

        // Check Area is active
        $areaID = Area::find($checkLocationCovered);
        if ($areaID && $areaID->status == 0) {
            return $this->responseError(422, __('core::app.areaNotActive'));
        }

        // Get Area Founded from the given location
        $data['area_id'] = $checkLocationCovered;
        $address = $this->customerAddressRepository->create($data);

        $address = new AddressSingle($address);

        return $this->responseSuccess($address,'Customer Address has been succussfully created!');
    }

    public function update(CustomerAddress $address, CustomerAddressUpdateRequest $request){
        $data=$request->only('area_id', 'icon_id', 'name','address','building_no','floor_no','apartment_no','landmark','location','phone');
        $data['latitude'] = $data['location']['lat'];
        $data['longitude'] = $data['location']['lng'];
        $data['customer_id'] = $this->customer->id;

        if (!isset($data['phone']) || empty($data['phone'])) {
            $data['phone'] = $this->customer->phone;
        }

        // First Check that given location is covered by Robosto
        $checkLocationCovered = (new CheckPointInArea($data['location']))->check();
        if (!$checkLocationCovered) {
            // Save the address with not covered status
            $data['covered'] = '0';
            $this->customerAddressRepository->create($data);
            return $this->responseError(422, __('core::app.areaNotCovered'));;
        }

        // Get Area Founded from the given location
        $data['area_id'] = $checkLocationCovered;

        $addressUpdated = $this->customerAddressRepository->update($data,$address->id);

        $addressUpdated = new AddressSingle($addressUpdated);

        return $this->responseSuccess($addressUpdated,'Customer Address has been succussfully updated!!');
    }

    /**
     * @param CustomerAddress $address
     * 
     * @return [type]
     */
    public function delete(CustomerAddress $address)
    {
        $notAvailableStatus = [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED, Order::STATUS_CANCELLED_FOR_ITEMS, Order::STATUS_RETURNED];

        // Get Orders that belongs to this address and still in progress
        $order = Order::whereNotIn('status', $notAvailableStatus)
                        ->where('customer_id', $this->customer->id)
                        ->where('address_id', $address->id)
                        ->first();
        
        // in case, there is one order in progress, the customer cannot delete this address yet.
        if ($order) {
            return $this->responseError(422, __('customer::app.thisAddressInOrder'));
        }

        // Else, Delete the address
        $this->customerAddressRepository->delete($address->id);

        return $this->responseSuccess(null,'Customer Address has been succussfully Deleted!');
    }


}
