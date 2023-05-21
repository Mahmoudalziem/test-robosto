<?php

namespace Webkul\Shipping\Http\Controllers\Api\Admin\V1;

use App\Jobs\DispatchShippment;
use App\Jobs\PickupShippment;
use App\Jobs\ShippmentOrderRouter;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Shipping\Http\Requests\CreatePickupLocationRequest;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderLogsActual;
use Webkul\Shipping\Exports\ShippmentsExport;
use Webkul\Shipping\Http\Requests\CreateBulkShippmentsRequest;
use Webkul\Shipping\Http\Requests\CreateCustomerShippingAddressRequest;
use Webkul\Shipping\Http\Requests\CreateShippmentRequest;
use Webkul\Shipping\Http\Requests\SetStatusRequest;
use Webkul\Shipping\Http\Resources\PickUpLocationResource;
use Webkul\Shipping\Http\Resources\ShippingAddressResource;
use Webkul\Shipping\Http\Resources\ShippmentAll;
use Webkul\Shipping\Http\Resources\ShippmentResource;
use Webkul\Shipping\Http\Resources\ShippmentSingle;
use Webkul\Shipping\Http\Resources\ShippmentTransferAll;
use Webkul\Shipping\Http\Resources\ShippmentTransferSingle;
use Webkul\Shipping\Imports\ShippmentsImport;
use Webkul\Shipping\Models\Shippment;
use Webkul\Shipping\Models\ShippmentLogs;
use Webkul\Shipping\Models\ShippmentTransfer;
use Webkul\Shipping\Repositories\PickupLocationRepository;
use Webkul\Shipping\Repositories\ShippingAddressRepository;
use Webkul\Shipping\Repositories\ShippmentBulkTransferRepository;
use Webkul\Shipping\Repositories\ShippmentRepository;
use Webkul\Shipping\Repositories\ShippmentTransferRepository;
use Webkul\Customer\Http\Controllers\Auth\SMSTrait;
use Webkul\Shipping\Http\Requests\CreateBulkTransferShippments;
use Webkul\Shipping\Http\Requests\CreateShipperRequest;
use Webkul\Shipping\Http\Requests\UpdateShippmentPriceRequest;
use Webkul\Shipping\Http\Resources\ShippersAll;
use Webkul\Shipping\Http\Resources\ShippmentBulkTransferAll;
use Webkul\Shipping\Http\Resources\ShippmentBulkTransferSingle;
use Webkul\Shipping\Http\Resources\SimpleShippmentAll;
use Webkul\Shipping\Models\Shipper;
use Webkul\Shipping\Repositories\ShipperRepository;

class ShippingController extends BackendBaseController
{
    use SMSTrait;

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;
    protected $pickupLocationRepository;
    protected $shippmentRepository;
    protected $shippingAddressRepository;
    protected $shippmentTransferRepository;
    protected $shippmentBulkTransferRepository;
    protected $shipperRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PickupLocationRepository $pickupLocationRepository, ShippmentRepository $shippmentRepository, ShippingAddressRepository $shippingAddressRepository, ShippmentTransferRepository $shippmentTransferRepository , ShippmentBulkTransferRepository $shippmentBulkTransferRepository , ShipperRepository $shipperRepository)
    {
        $this->pickupLocationRepository = $pickupLocationRepository;
        $this->shippmentRepository = $shippmentRepository;
        $this->shippingAddressRepository = $shippingAddressRepository;
        $this->shippmentTransferRepository = $shippmentTransferRepository;
        $this->shippmentBulkTransferRepository = $shippmentBulkTransferRepository;
        $this->shipperRepository = $shipperRepository;
    }

    public function createPickupLocation(CreatePickupLocationRequest $request)
    {
        $data = $request->only("name", "phone", "address", "area_id", "location");
        auth()->user()->pickupLocation()->create(PickUpLocationResource::DTO($data));
        return $this->responseSuccess();
    }

    public function showPickupLocations()
    {
        return $this->responseSuccess(["locations" => auth()->user()->pickupLocation]);
    }


    public function createShippment(CreateShippmentRequest $request)
    {
        $data = $request->only("merchant","customer_name", "customer_email", "customer_phone", "customer_address","customer_landmark","customer_apartment_no", "customer_building_no","customer_floor_no","pickup_id", "items_count", "price", "note","description");
        $pickUpLocation = $this->pickupLocationRepository->findOneWhere(["shipper_id" => auth()->id(), "id" => $data["pickup_id"]]);
        if (!$pickUpLocation) {
            return $this->responseError(401, "wrong pickup location");
        }
        DB::beginTransaction();
        try {
            $address = $this->shippingAddressRepository->createShippingAddress(ShippingAddressResource::DTO($data));
            $data['shipping_address_id'] = $address->id;
            $data['area_id'] = $pickUpLocation->area_id;
            $data['warehouse_id'] = $pickUpLocation->warehouse_id;
            $shippment = $this->shippmentRepository->createShippment(ShippmentResource::DTO($data));
            Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_CREATED]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseError(403, "shippment creation error");
        }
        DB::commit();
        PickupShippment::dispatch($shippment->id)->delay(Carbon::now()->addSeconds(5));
        return $this->responseSuccess(["shipment_id"=>$shippment->shipping_number]);
    }
    public function createManyShippments(CreateBulkShippmentsRequest $request)
    {
        $pickUpLocation = $this->pickupLocationRepository->findOneWhere(["shipper_id" => auth()->id(), "id" =>$request->pickup_id]);
        if (!$pickUpLocation) {
            return $this->responseError(401, "wrong pickup location");
        }
        $file = $request->file;
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();
        $this->checkUploadedFileProperties($extension, $fileSize);
        $import = new ShippmentsImport($pickUpLocation,$this->shippmentRepository,$this->shippingAddressRepository);
        Excel::import($import, $request->file);
        if(count($import->notCreated)>0){
            return $this->responseSuccess($import->notCreated,"we faced issues with these rows");
        }
        return $this->responseSuccess();
    }
    public function shippmentProfile($id)
    {
        $shippment = $this->shippmentRepository->findOneWhere(['id'=>$id,'shipper_id'=>auth()->id()]);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        $data = new ShippmentSingle($shippment);
        return $this->responseSuccess($data);
    }
    public function shippmentFullProfile($id)
    {
        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        $data = new ShippmentSingle($shippment);
        return $this->responseSuccess($data);
    }
    public function listShippments(Request $request)
    {
        $shippmentsRequests = $this->shippmentRepository->list($request,'shipper',false);
        $data = new ShippmentAll($shippmentsRequests);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function listAllShippments(Request $request)
    {
        $shippmentsRequests = $this->shippmentRepository->list($request,'admin',false);
        $data = new ShippmentAll($shippmentsRequests);
        return $this->responsePaginatedSuccess($data, null, $request);
    }
    public function checkUploadedFileProperties($extension, $fileSize): void
    {
        $valid_extension = array("csv", "xlsx"); //Only want csv and excel files
        $maxFileSize = 2097152; // Uploaded file size limit is 2mb
        if (in_array(strtolower($extension), $valid_extension)) {
            if ($fileSize <= $maxFileSize) {
            } else {
                throw new \Exception('No file was uploaded', 413); //413 error
            }
        } else {
            throw new \Exception('Invalid file extension', 415); //415 error
        }
    }

    public function createCustomerShippingAddress(CreateCustomerShippingAddressRequest $request)
    {
        $data = $request->only("tracking_number", "phone_number","address", "location", "scheduled_at");
        $this->shippingAddressRepository->createCustomerFullShippingAddress($data);
        return $this->responseSuccess();
    }

    public function listShippmentTransfers(Request $request)
    {
        $transferRequests = $this->shippmentTransferRepository->list($request);
        $data = new ShippmentTransferAll($transferRequests); // using InventoryTransacttion repository
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function listShippmentBulkTransfers(Request $request){
        $transferRequests = $this->shippmentBulkTransferRepository->list($request);
        $data = new ShippmentBulkTransferAll($transferRequests); // using InventoryTransacttion repository
        return $this->responsePaginatedSuccess($data, null, $request);
    }
    public function shippmentBulkTransferProfile($id)
    {
        $transfer = $this->shippmentBulkTransferRepository->with('bulkTransferItems')->findOrFail($id);
        $data = new ShippmentBulkTransferSingle($transfer);
        return $this->responseSuccess($data);
    }

    public function shippmentTransferProfile($id)
    {
        $transfer = $this->shippmentTransferRepository->findOrFail($id);
        $data = new ShippmentTransferSingle($transfer);
        return $this->responseSuccess($data);
    }
    public function setStatus($id, SetStatusRequest $request)
    {
        $request = $request->only('status');
        $transfer = $this->shippmentTransferRepository->findOrFail($id);
        $transferData = $this->shippmentTransferRepository->updateTransferStatus($transfer, $request);
        return $this->responseSuccess($transferData);
    }

    public function cancelShippment($id){
        return $this->responseError(404, "SORRY");

        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if($shippment->current_status!=Shippment::CURRENT_STATUS_PENDING_COLLECTING_CUSTOMER_INFO){
            return $this->responseError(404, "shipment is not cancelable");
        }
        $shippment->update(["status"=>Shippment::STATUS_FAILED,"current_status"=>Shippment::CURRENT_STATUS_FAILED_COLLECTING_CUSTOMER_INFO]);
        Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_FAILED_COLLECTING_CUSTOMER_INFO]);
        return $this->responseSuccess();
    }
    public function cancelShipperShippment($id){
        $shippment = $this->shippmentRepository->findOneWhere(['id'=>$id,'shipper_id'=>auth()->id()]);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if($shippment->current_status!=Shippment::CURRENT_STATUS_PENDING_PICKING_UP_ITEMS){
            return $this->responseError(404, "shipment is not cancelable");
        }
        $pickupOrder = Order::where('shippment_id',$shippment->id)->where('status',Order::STATUS_READY_TO_PICKUP)->where('customer_id',null)->first();
        if(!$pickupOrder){
            return $this->responseError(404, "shipment is not cancelable");
        }
        $pickupOrder->update(["status"=>Order::STATUS_CANCELLED]);

        Event::dispatch('order.actual_logs', [$pickupOrder, OrderLogsActual::ORDER_CANCELLED]);
        Event::dispatch('admin.log.activity', ['order-cancelled', 'order', $pickupOrder, auth()->user(), $pickupOrder]);
        Event::dispatch('driver.order-cancelled', $pickupOrder->id);
        $shippment->update(["status"=>Shippment::STATUS_FAILED,"current_status"=>Shippment::CURRENT_STATUS_FAILED_PICKING_UP_ITEMS]);
        Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_PICK_UP_ORDER_FAILED]);
        return $this->responseSuccess();
    }

    public function exportAdmin(Request $request) {
        return Excel::download(new ShippmentsExport($this->shippmentRepository,'admin'), 'shipments.xlsx');
    }

    public function exportShipper(Request $request) {
        return Excel::download(new ShippmentsExport($this->shippmentRepository,'shipper'), 'shipments.xlsx');
    }

    public function resetCustomerInfo($id){
        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if($shippment->current_status!=Shippment::CURRENT_STATUS_PENDING_TRANSFER && $shippment->current_status!=Shippment::CURRENT_STATUS_DISPATCHING){
            return $this->responseError(404, "shipment is not resetable");
        }
        if($shippment->current_status==Shippment::CURRENT_STATUS_PENDING_TRANSFER){
            $this->shippmentTransferRepository->where("shippment_id",$id)->whereIn('status',[ShippmentTransfer::STATUS_PENDING,ShippmentTransfer::STATUS_ON_THE_WAY])->update(["status"=>ShippmentTransfer::STATUS_CANCELLED]);
        }
        if($shippment->current_status==Shippment::CURRENT_STATUS_DISPATCHING){
             Order::where('shippment_id',$id)->whereNotNull('customer_id')->whereIn('status',[Order::STATUS_PREPARING,Order::STATUS_READY_TO_PICKUP,Order::STATUS_ON_THE_WAY,Order::STATUS_AT_PLACE , Order::STATUS_SCHEDULED])->update(["status"=>Order::STATUS_CANCELLED,"cancelled_reason"=>"Reseting Customer Location Info For Shippment"]);
        }
        $shippment->update(["status"=>Shippment::STATUS_PENDING,"current_status"=>Shippment::CURRENT_STATUS_PENDING_COLLECTING_CUSTOMER_INFO]);
        Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_PENDING_COLLECTING_CUSTOMER_INFO]);
        return $this->responseSuccess();
    }

    public function redispatchPickUpOrder($id){
        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if($shippment->current_status!=Shippment::CURRENT_STATUS_FAILED_PICKING_UP_ITEMS){
            return $this->responseError(404, "can not redispatch pickup order");
        }
        $shippment->update(["status"=>Shippment::STATUS_PENDING,"current_status"=>Shippment::CURRENT_STATUS_PENDING_PICKING_UP_ITEMS]);
        PickupShippment::dispatch($shippment->id);
        return $this->responseSuccess();
    }

    public function redispatchNewDeliveryOrder($id){
        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if($shippment->current_status!=Shippment::CURRENT_STATUS_FAILED){
            return $this->responseError(404, "can not redispatch new delivery order");
        }
        DispatchShippment::dispatch($shippment , 4);
        return $this->responseSuccess();
    }
    public function markShippmentAsPickedUp($id){
        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if($shippment->current_status!=Shippment::CURRENT_STATUS_PENDING_PICKING_UP_ITEMS){
            return $this->responseError(404, "can not redispatch new delivery order");
        }
        $order = Order::where('shippment_id',$id)->whereNull('customer_id')->where('status',Order::STATUS_SCHEDULED)->first();

        if(!$order){
            return $this->responseError(404, "there is no scheduled pickup orders for this shipment");
        }
        $order->update(["status"=>Order::STATUS_DELIVERED]);
        Event::dispatch('driver.order-delivered', $order->id);
        if($order->driver_id){
            Event::dispatch('driver.order-delivered-bonus', $order->driver_id);
        }
        Event::dispatch('app.order.delivered', $order);
        Event::dispatch('order.actual_logs', [$order, OrderLogsActual::ORDER_DRIVER_ITEMS_DELIVERED]);
        Event::dispatch('app.order.status_changed', $order);
        $mainWarehouse = config('robosto.STOCK_WAREHOUSE');
        $shippment->update(['warehouse_id'=>$mainWarehouse['warehouse_id'],'area_id'=>$mainWarehouse['area_id'],'current_status'=>Shippment::CURRENT_STATUS_PENDING_DISTRIBUTION,'pickup_date'=>Carbon::now()]);
        Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_ITEMS_PICKED_UP]);
        Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_PENDING_DISTRIBUTION]);
        // ShippmentOrderRouter::dispatch($order);
        // $phone =$shippment->shippingAddress->phone;
        // $number=$shippment->shipping_number;
        // $shippingPortalLink = config('robosto.SHIPPING_PORTAL_URL');
        // $link = "$shippingPortalLink/shipment-tracking?tracking_number=$number&phone_number=$phone";
        // $shipper = $shippment->merchant?$shippment->merchant:$shippment->shipper->name;
        // $this->sendSMS($shippment->shippingAddress->phone, " عندك اوردر شحن من شركة $shipper كمل البيانات واختار وقت التوصيل  $link ");
        return $this->responseSuccess();
    }

    public function getDistibutableShippments(Request $request){
        $shippmentsRequests = $this->shippmentRepository->listTransferableShippments($request , $request->warehouse_id);
        $data = new SimpleShippmentAll($shippmentsRequests);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function createBulkTransfer(CreateBulkTransferShippments $request){
        $data = $request->only("shipments", "from_warehouse", "to_warehouse");
        if($data['from_warehouse']==$data['to_warehouse']){
            return $this->responseError(404, "you can not transfer to the same warehouse");
        }
        $allShipments = $this->shippmentRepository->whereIn('id',$data['shipments'])->where(function ($q) use ($data) {
            $q->where('current_status','!=',Shippment::CURRENT_STATUS_PENDING_DISTRIBUTION)->orWhere('warehouse_id','!=',$data["from_warehouse"]);
        });
        if($allShipments->count() > 0){
            return $this->responseError(404, "not all shipments are ready to be transferred");
        }
        $transferItems = [];
        $totalShipmentsWithoutExclude= $this->shippmentRepository->whereIn('id',$data['shipments']);
        foreach($totalShipmentsWithoutExclude->get() as $oneShippment){
            $transferItems[] = ["shippment_id"=>$oneShippment->id];
            Event::dispatch('shippment.log',[$oneShippment,ShippmentLogs::SHIPPMENT_DISTRIBUTION_STARTED]);
        }
        $this->shippmentBulkTransferRepository->createBulkTransfer($data,$transferItems);
        $totalShipmentsWithoutExclude->update(['current_status'=>Shippment::CURRENT_STATUS_PENDING_DISTRIBUTING]);
        return $this->responseSuccess();
    }

    public function setBulkTransferStatus($id, SetStatusRequest $request){
        $request = $request->only('status');
        $transfer = $this->shippmentBulkTransferRepository->with('bulkTransferItems.shippment')->findOrFail($id);
        $transferData = $this->shippmentBulkTransferRepository->updateBulkTransferStatus($transfer, $request);
        return $this->responseSuccess($transferData);
    }

    public function dispatchPendingDistributionShippments($id){
        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if($shippment->current_status!=Shippment::CURRENT_STATUS_PENDING_DISTRIBUTION){
            return $this->responseError(404, "can not redispatch new delivery order");
        }
        if(!$shippment->customer_address_id){
            return $this->responseError(404, "can not dispatch without customer info");
        }
        $mainWarehouse = config('robosto.STOCK_WAREHOUSE');
        if($shippment->warehouse_id==$mainWarehouse["warehouse_id"]){
            return $this->responseError(404, "transfer this shippment first");
        }
        DispatchShippment::dispatch($shippment , 4);
    }

    public function markShippmentsAsPendingDistribution($id){
        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if(
            $shippment->current_status != Shippment::CURRENT_STATUS_DISPATCHING &&
            $shippment->current_status != Shippment::CURRENT_STATUS_FAILED
          ){
            return $this->responseError(404, "can not redistribute");
        }
        Order::where('shippment_id',$id)->whereNotNull('customer_id')->whereIn('status',[Order::STATUS_PREPARING,Order::STATUS_READY_TO_PICKUP,Order::STATUS_ON_THE_WAY,Order::STATUS_AT_PLACE , Order::STATUS_SCHEDULED])->update(["status"=>Order::STATUS_CANCELLED,"cancelled_reason"=>"Cancelling for distribution"]);
        $shippment->update(["status"=>Shippment::STATUS_PENDING,"current_status"=>Shippment::CURRENT_STATUS_PENDING_DISTRIBUTION]);
        return $this->responseSuccess();
    }


    public function returnToVendor($id){
        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if(
            $shippment->current_status != Shippment::CURRENT_STATUS_FAILED
          ){
            return $this->responseError(404, "can not return to vendor");
        }
        $shippment->update(["current_status"=>Shippment::CURRENT_STATUS_RETURNED_TO_VENDOR]);
        Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_RETURNED_TO_VENDOR]);
        return $this->responseSuccess();
    }

    public function settleShippment($id){
        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if(
            $shippment->current_status != Shippment::CURRENT_STATUS_RETURNED_TO_VENDOR &&
            $shippment->current_status != Shippment::CURRENT_STATUS_DELIVERED
          ){
            return $this->responseError(404, "can not settled");
        }
        $shippment->update(["is_settled"=>true]);
        return $this->responseSuccess();
    }

    public function rtsShippment($id){
        $shippment = $this->shippmentRepository->findOrFail($id);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if(
            $shippment->current_status != Shippment::CURRENT_STATUS_FAILED ||
            $shippment->is_rts == 1
          ){
            return $this->responseError(404, "can not rts");
        }
        $shippment->update(["is_rts"=>true]);
        return $this->responseSuccess();
    }

    public function updateShippmentPrice(UpdateShippmentPriceRequest $request){
        $data = $request->only("id" , "amount");
        $shippment = $this->shippmentRepository->findOneWhere(["shipper_id" => auth()->id(), "id" => $data["id"]]);
        if(!$shippment){
            return $this->responseError(404, "could not find shipment");
        }
        if($shippment->current_status == Shippment::STATUS_DELIVERED){
            return $this->responseError(404, "already delivered can not update amount");
        }
        Order::where('shippment_id',$data["id"])->whereNotNull('customer_id')->update(["sub_total"=>$data["amount"],"final_total"=>$data["amount"]]);
        $shippment->update(["final_total"=>$data["amount"]]);
        return $this->responseSuccess();
    }

    public function listShippers(Request $request){
        $shippersRequests = $this->shipperRepository->list($request);
        $data = new ShippersAll($shippersRequests);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function createShipper(CreateShipperRequest $request){
        $data = $request->only("name" , "email" , "password");
        Shipper::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password'=>Hash::make($data["password"])
        ]);
        return $this->responseSuccess();
    }
}
