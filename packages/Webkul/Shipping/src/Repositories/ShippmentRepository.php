<?php

namespace Webkul\Shipping\Repositories;

use App\Jobs\DispatchShippment;
use Carbon\Carbon;
use Webkul\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\Channel;
use Webkul\Customer\Http\Controllers\Auth\SMSTrait;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shipping\Models\Shippment;
use Webkul\Shipping\Models\ShippmentLogs;

class ShippmentRepository extends Repository
{
    use SMSTrait;

    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository, App $app)
    {
        $this->orderRepository = $orderRepository;
        parent::__construct($app);
    }
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Shipping\Contracts\Shippment';
    }
    public function list($request,$guard='admin' , $report = false) {
        Log::info($request);
        Log::info($guard);
        Log::info(auth('shipper')->id());
        $query = $this->newQuery();
        if($guard=='shipper'){
            $query = $this->newQuery()->where("shipper_id",auth('shipper')->id());
        }
        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        if ($request->exists('date_from') && !empty($request['date_from']) && $request->exists('date_to') && !empty($request['date_to'])) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request['date_from'] . ' 00:00:00';
                $dateTo = $request['date_to'] . ' 23:59:59';
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            });
        }
        if ($request->exists('pickup_date_from') && !empty($request['pickup_date_from']) && $request->exists('pickup_date_to') && !empty($request['pickup_date_to'])) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request['pickup_date_from'] . ' 00:00:00';
                $dateTo = $request['pickup_date_to'] . ' 23:59:59';
                $q->whereBetween('pickup_date', [$dateFrom, $dateTo]);
            });
        }
        if ($request->exists('status') && !empty($request['status'])) {
            $query->where('status',$request['status']);
        }

        if ($request->exists('current_status') && !empty($request['current_status'])) {
            $query->where('current_status',$request['current_status']);
        }



        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->where(function ($query) use ($request) {
                $numbers = trim($request->filter);
                $numbers = explode(' ', $numbers);
                if (count($numbers) > 1) {
                    $query->whereIn('shipping_number', $numbers);
                } else {
                    $query->where('shipping_number', 'LIKE', '%' . trim($request->filter) . '%')
                        ->orWhereHas('shipper', function ($q) use ($request) {
                            $q->where('name', 'LIKE', '%' . trim($request->filter) . '%');
                        })
                        ->orWhereHas('shippingAddress', function ($q) use ($request) {
                            $q->where('name', 'LIKE', '%' . trim($request->filter) . '%')
                                ->orWhere('phone', 'LIKE', '%' . trim($request->filter) . '%')
                                ->orWhere('address', 'LIKE', '%' . trim($request->filter) . '%');
                        })
                        ->orWhere('note', 'LIKE', '%' . trim($request->filter) . '%')
                        ->orWhere('description', 'LIKE', '%' . trim($request->filter) . '%')
                        ->orWhere('merchant', 'LIKE', '%' . trim($request->filter) . '%')
                    ;
                }
            });
        }
        

        if($report){
            return $query->with('shipper','warehouse','orders','orders.actualLogs','orders.cancelReason','logs');
        }
        Log::info($query->toSql());
        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }
    public function listTransferableShippments($request , $warehouse_id) {
        $query = $this->newQuery()->where("warehouse_id",$warehouse_id)->where('current_status',Shippment::CURRENT_STATUS_PENDING_DISTRIBUTION);
        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }
        if ($request->exists('filter') && !empty($request['filter'])) {
            $numbers=trim($request->filter);
            $numbers = explode(',', $numbers);
            if(count($numbers)>1){
                $query->whereIn('shipping_number',$numbers);
            }else{
                $query->where('shipping_number', 'LIKE', '%' . trim($request->filter) . '%');
            }
        }

        if ($request->exists('key') && !empty($request['key'])) {
            $query->where('shipping_number', 'LIKE', '%' . trim($request->key) . '%');
        }
        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }
    public function createShippment($shippmentInfo)
    {
        return auth()->user()->shippments()->create($shippmentInfo);
    }
    public function createPickUpOrder(Shippment $shippment)
    {
        $warehouse = Warehouse::where('area_id', $shippment->area_id)->where('status', 1)->first();
        $this->orderRepository->createShippingOrder(
            [
                "shippment_id" => $shippment->id,
                "area_id" => $shippment->area_id,
                "warehouse_id" => $warehouse->id,
                "final_total" => 0,
                "items_count" => $shippment->items_count,
                "note" => "PICK UP ORDER for shippment " . $shippment->id,
                "channel_id" => Channel::SHIPPING_SYSTEM,
                "warehouse_address" => $warehouse,
                "scheduled_at"=>Carbon::now()->addDays(3)->format('Y-m-d H:i:s')
            ],
            true
        );
        Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_PICK_UP_ORDER_CREATED]);
    }


    public function createShippmentOrder(Shippment $shippment, $trial)
    {
        Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_TRIAL_CREATED]);
        $customerAddress = CustomerAddress::find($shippment->customer_address_id);
        $this->orderRepository->createShippingOrder(
            [
                "shippment_id" => $shippment->id,
                "area_id" => $shippment->area_id,
                "warehouse_id" => $shippment->warehouse_id,
                "final_total" => $shippment->final_total,
                "items_count" => $shippment->items_count,
                "note" => "SHIPPING ORDER for shippment " . $shippment->id,
                "channel_id" => Channel::SHIPPING_SYSTEM,
                "customer_address" => $customerAddress,
                "customer_id" => $customerAddress->customer_id,
                "address_id" => $customerAddress->id,
                "scheduled_at" => $shippment->first_trial_date
            ]
        );
        $shippment->update(['status' => $trial == 1 ? Shippment::STATUS_SCHEDULED : Shippment::STATUS_RESCHEDULED, 'current_status' => Shippment::CURRENT_STATUS_DISPATCHING]);
    }


    public function routeShippment(Order $order , $config)
    {
        if ($order->status == Order::STATUS_DELIVERED) {
            $this->shippmentDelivered($order);
        }
        if ($order->status == Order::STATUS_CANCELLED) {
            $this->shippmentCancelled($order,$config);
        }
    }

    public function shippmentDelivered(Order $order)
    {
        $shippment = $this->find($order->shippment_id);
        if (!$order->customer_id) {
            Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_ITEMS_PICKED_UP]);
            Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_PENDING_COLLECTING_CUSTOMER_INFO]);
            //order is a pickup and its picked up successfully
            $shippment->update(["current_status" => Shippment::CURRENT_STATUS_PENDING_COLLECTING_CUSTOMER_INFO]);
            // $phone =$shippment->shippingAddress->phone;
            // $number=$shippment->shipping_number;
            // $shippingPortalLink = config('robosto.SHIPPING_PORTAL_URL');
            // $link = "$shippingPortalLink/shipment-tracking?tracking_number=$number&phone_number=$phone";
            // $shipper = $shippment->merchant?$shippment->merchant:$shippment->shipper->name;
            // $this->sendSMS($shippment->shippingAddress->phone, " عندك اوردر شحن من شركة $shipper كمل البيانات واختار وقت التوصيل  $link ");
        }else{
            Event::dispatch('shippment.log',[$shippment,ShippmentLogs::SHIPPMENT_DELIVERED]);
            $shippment->update(["current_status" => Shippment::CURRENT_STATUS_DELIVERED,"status"=>Shippment::STATUS_DELIVERED]);
            //check if there is other orders
            Order::where('shippment_id',$shippment->id)->whereNotIn('status',[Order::STATUS_CANCELLED , Order::STATUS_DELIVERED])->update(["status"=>Order::STATUS_CANCELLED,"cancelled_reason"=>"Reseting Customer Location Info For Shippment , dup"]);
        }
    }
    public function shippmentCancelled(Order $order ,$config)
    {
        if($order->customer_id){
            Event::dispatch('shippment.log',[$order->shippment,ShippmentLogs::SHIPPMENT_TRIAL_FAILED]);
            $sameShippmentOrders = $this->orderRepository->findWhere(["shippment_id"=>$order->shippment_id, "customer_id"=>$order->customer_id]);
            $cancelAll = $config["cancel_all"]??null;
            if(!$cancelAll  && count($sameShippmentOrders)<4){
                Event::dispatch('shippment.log',[$order->shippment,ShippmentLogs::SHIPPMENT_TRIAL_RESCHEDULED]);
                $order->shippment()->update(["first_trial_date"=>Carbon::createFromFormat('Y-m-d H:i:s', $order->shippment->first_trial_date)->addDay()]);
                DispatchShippment::dispatch($order->shippment,count($sameShippmentOrders)+1);
            }else{
                Event::dispatch('shippment.log',[$order->shippment,ShippmentLogs::SHIPPMENT_FAILED]);
                $order->shippment()->update(["status"=>Shippment::STATUS_FAILED,"current_status"=>Shippment::CURRENT_STATUS_FAILED]);
            }
        }else{
            Event::dispatch('shippment.log',[$order->shippment,ShippmentLogs::SHIPPMENT_PICK_UP_ORDER_FAILED]);
            $order->shippment()->update(["status"=>Shippment::STATUS_FAILED,"current_status"=>Shippment::CURRENT_STATUS_FAILED_PICKING_UP_ITEMS]);
        }
    }
}
