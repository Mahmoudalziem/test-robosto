<?php

namespace Webkul\Shipping\Imports;

use App\Exceptions\HttpApiValidationException;
use App\Jobs\PickupShippment;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\DB;
use Webkul\Shipping\Http\Resources\ShippingAddressResource;
use Webkul\Shipping\Http\Resources\ShippmentResource;

class ShippmentsImport implements ToModel, WithHeadingRow , SkipsEmptyRows
{
    public $notCreated;
    public $pickUpLocation;
    protected $shippmentRepository;
    protected $shippingAddressRepository;
    public function __construct($pickUpLocation, $shippmentRepository, $shippingAddressRepository)
    {
        $this->notCreated = collect();
        $this->pickUpLocation = $pickUpLocation;
        $this->shippmentRepository = $shippmentRepository;
        $this->shippingAddressRepository = $shippingAddressRepository;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->validateHeaders(array_keys($row));
        $dispatch = true;
        DB::beginTransaction();
        try {
            $address = $this->shippingAddressRepository->createShippingAddress(ShippingAddressResource::DTO($row));
            $row['shipping_address_id'] = $address->id;
            $row['area_id'] = $this->pickUpLocation->area_id;
            $row['warehouse_id'] = $this->pickUpLocation->warehouse_id;
            $row['pickup_id']=$this->pickUpLocation->id;
            $shippment = $this->shippmentRepository->createShippment(ShippmentResource::DTO($row));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notCreated->push($row);
            $dispatch = false;
        }
        if ($dispatch) {
            DB::commit();
            PickupShippment::dispatch($shippment->id)->delay(Carbon::now()->addSeconds(5));
        }
    }
    private function validateHeaders($data)
    {
        $mustMatch = ["merchant","customer_name", "customer_email", "customer_phone", "customer_address", "items_count", "price","customer_landmark","customer_apartment_no","customer_building_no","customer_floor_no","note","description"];
        $matched = array_intersect($mustMatch, $data);
        if (count($matched) != count($mustMatch)) {
            throw new HttpApiValidationException(401, "invalid headers");
        }
    }
}
