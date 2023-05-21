<?php

namespace Webkul\Shipping\Repositories;

use App\Exceptions\HttpApiValidationException;
use App\Jobs\DispatchShippment;
use Webkul\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Models\Channel;
use Webkul\Core\Services\Measure;
use Webkul\Customer\Models\Avatar;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Shipping\Http\Resources\ShippingSystemCustomerAddressResource;
use Webkul\Shipping\Http\Resources\ShippingSystemNewCustomerResource;
use Webkul\Shipping\Models\Shippment;
use Webkul\Shipping\Models\ShippmentLogs;

class ShippingAddressRepository extends Repository
{
    protected $shippmentRepository;
    protected $customerRepository;
    protected $customerAddressRepository;
    protected $shippmentTransferRepository;

    public function __construct(ShippmentRepository $shippmentRepository, CustomerRepository $customerRepository, CustomerAddressRepository $customerAddressRepository, ShippmentTransferRepository $shippmentTransferRepository, App $app)
    {
        $this->shippmentRepository = $shippmentRepository;
        $this->customerRepository = $customerRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->shippmentTransferRepository = $shippmentTransferRepository;
        parent::__construct($app);
    }
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Shipping\Contracts\ShippingAddress';
    }
    public function createShippingAddress($addressInfo)
    {
        return auth()->user()->shippingAddress()->create($addressInfo);
    }

    public function createCustomerFullShippingAddress($data)
    {
        $shippment = $this->shippmentRepository->findOneWhere(['shipping_number'=>$data["tracking_number"]]);
        if(!$shippment){
            throw new HttpApiValidationException(404,"لم نتمكن من ايجاد الشحنة");
        }
        if($shippment->shippingAddress->phone!=$data["phone_number"]){
            throw new HttpApiValidationException(404,"لم نتمكن من ايجاد الشحنة");
        }
        if(
            $shippment->current_status != Shippment::CURRENT_STATUS_PENDING_COLLECTING_CUSTOMER_INFO && 
            $shippment->current_status != Shippment::CURRENT_STATUS_PENDING_DISTRIBUTION && 
            $shippment->current_status != Shippment::CURRENT_STATUS_PENDING_DISTRIBUTING
          )
        {
            throw new HttpApiValidationException(404,"لا يمكن إضافة عنوان جديد");
        }
        $deliveryAddress = $this->createCustomerAddress(["shippment_shipping_address" => $shippment->shippingAddress, "nearest_warehouse" => $shippment->warehouse, "customer_location" => $data['location']]);
        Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_COLLECTED_CUSTOMER_INFO]);
        $shippment->update(['customer_address_id' => $deliveryAddress->id, 'first_trial_date' => $data['scheduled_at']]);
        if($shippment->current_status==Shippment::CURRENT_STATUS_PENDING_COLLECTING_CUSTOMER_INFO){
            $shippment->update(['current_status'=>Shippment::CURRENT_STATUS_DISPATCHING]);
            Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_DISPATCHING]);
            DispatchShippment::dispatch($shippment, 1);
        }
    }

    public function getNearestWareHouseToCustomerAddress($addressLat, $addressLong)
    {
        $warehouses = Warehouse::where('status', 1)->get();
        $pointsArray = [];
        foreach ($warehouses as $warehouse) {
            $pointsArray[] = [
                $warehouse->latitude, $warehouse->longitude, $warehouse->id
            ];
        }
        $addressDistance = Measure::distanceMany($addressLat, $addressLong, $pointsArray, 'K');
        $array_column = array_column($addressDistance, 'distance');
        array_multisort($array_column, SORT_ASC, $addressDistance);
        $nearestWarehouse = $warehouses->find($addressDistance[0]['data']);
        return $nearestWarehouse;
    }

    public function createCustomerAddress($data)
    {
        $phone = $data["shippment_shipping_address"]->phone;
        $oldPhone = $data["shippment_shipping_address"]->phone;
        if (str_starts_with($phone, '201')) {
            $phone =  substr($phone, 1);
        }
        if (str_starts_with($phone, '+201')) {
            $phone =  substr($phone, 2);
        }
        $customer = $this->customerRepository->findOneWhere(["phone" => $phone]);
        if(!$customer){
            $customer = $this->customerRepository->findOneWhere(["phone" => $oldPhone]);
        }
        if (!$customer) {
            $customer = $this->customerRepository->create(ShippingSystemNewCustomerResource::DTO($data));
        }
        $address = $this->customerAddressRepository->create(ShippingSystemCustomerAddressResource::DTO($customer, $data));
        return $address;
    }
}
