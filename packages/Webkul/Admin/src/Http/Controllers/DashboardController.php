<?php

namespace Webkul\Admin\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Area\Models\Area;
use Webkul\Core\Models\Sold;
use Webkul\Product\Models\Product;
use Webkul\Category\Models\Category;
use Webkul\Customer\Models\CustomerProduct;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Promotion\Models\Promotion;
use Webkul\Sales\Models\Order;
use Webkul\Core\Models\Channel;
use Webkul\Driver\Models\Driver;
use Illuminate\Support\Collection;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Sales\Models\Order as OrderModel;
use Webkul\Admin\Repositories\Sales\OrderRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Repositories\Product\ProductRepository;
use Webkul\Admin\Http\Resources\Dashboard\OrdersResources;
use Webkul\Admin\Http\Resources\Dashboard\DriversResources;
use Webkul\Admin\Http\Resources\Dashboard\WarehousesResources;
use Webkul\Admin\Repositories\Customer\AdminCustomerRepository;
use Webkul\Sales\Models\OrderItem;
use Webkul\Admin\Http\Resources\Soldable\SoldableCategoryAll;
use Webkul\Admin\Http\Resources\Soldable\SoldablePrdouctAll;
use Webkul\Admin\Http\Resources\Inventory\InventoryAreaAll;

class DashboardController extends BackendBaseController {

    protected $orderRepository;
    protected $customerRepository;
    protected $productRepository;

    public function __construct(
            OrderRepository $orderRepository,
            AdminCustomerRepository $customerRepository,
            ProductRepository $productRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
    }

    public function index() {
        $countData = [];
        $orderStatusCollection = OrderModel::byArea()->selectRaw("
                                         COUNT(CASE WHEN `status` in ('" . OrderModel::STATUS_PENDING . "', '" . OrderModel::STATUS_WAITING_CUSTOMER_RESPONSE . "') THEN 1 END) AS 'pending_orders',
                                        COUNT(CASE WHEN `status` in ('" . OrderModel::STATUS_PREPARING . "', '" . OrderModel::STATUS_READY_TO_PICKUP . "', '" . OrderModel::STATUS_ON_THE_WAY . "', '" . OrderModel::STATUS_AT_PLACE . "') THEN 1 END) AS 'active_orders',
                                        COUNT(CASE WHEN `status` in ('" . OrderModel::STATUS_CANCELLED . "', '" . OrderModel::STATUS_CANCELLED_FOR_ITEMS . "'  ) THEN 1 END) AS 'cancelled_orders',
                                        COUNT(CASE WHEN `status` in ('" . OrderModel::STATUS_DELIVERED . "' ) THEN 1 END) AS 'delivered_orders',
                                        COUNT('id') AS 'total_orders'
                                        ")->get();
        $driverStatusCollection = Driver::byArea()->selectRaw("
                                        COUNT(CASE WHEN `availability` in ('" . Driver::AVAILABILITY_ONLINE . "','" . Driver::AVAILABILITY_IDLE . "') THEN 1 END) AS 'drivers_waiting',
                                        COUNT(CASE WHEN `availability` in ('" . Driver::AVAILABILITY_DELIVERY . "') THEN 1 END) AS 'drivers_on_the_way',
                                        COUNT(CASE WHEN `availability` in ('" . Driver::AVAILABILITY_BACK . "') THEN 1 END) AS 'drivers_on_the_way_back',
                                        COUNT(CASE WHEN `availability` in ('" . Driver::AVAILABILITY_BREAK . "' ) THEN 1 END) AS 'drivers_breack',
                                        COUNT(CASE WHEN `availability` in ('" . Driver::AVAILABILITY_ONLINE . "','" . Driver::AVAILABILITY_IDLE . "','". Driver::AVAILABILITY_DELIVERY .  "','".Driver::AVAILABILITY_BACK . "','" . Driver::AVAILABILITY_BREAK . "' ) THEN 1 END) AS 'drivers_active',                                            
                                        COUNT(CASE WHEN `is_online` = 1 THEN 1 END) AS 'drivers_online'
                                        ")->get();

        $countData['orders'] = $orderStatusCollection->map(function ($order) {
            return [
        'pending_orders' => $order->pending_orders,
        'active_orders' => $order->active_orders,
        'cancelled_orders' => $order->cancelled_orders,
        'delivered_orders' => $order->delivered_orders,
        'total_orders' => $order->total_orders,
            ];
        });
        $countData['drivers'] = $driverStatusCollection->map(function ($driver) {
            return [
        'drivers_waiting' => $driver->drivers_waiting,
        'drivers_on_the_way' => $driver->drivers_on_the_way,
        'drivers_on_the_way_back' => $driver->drivers_on_the_way_back,
        'drivers_breack' => $driver->drivers_breack,
        'drivers_active' => $driver->drivers_active,
        'drivers_online' => $driver->drivers_active,                
            ];
        });

        return $this->responseSuccess($countData);
    }

    public function totalStores(Request $request) {
        $countData = Warehouse::byArea()->count();
        return $this->responseSuccess($countData);
    }

    public function totalCategories(Request $request) {
        $countData = Category::count();
        return $this->responseSuccess($countData);
    }

    public function totalItems(Request $request) {
        $countData = Product::count();
        return $this->responseSuccess($countData);
    }

    public function itemsExpiredSoon(Request $request) {
        $fromDate = Carbon::now()->startOfDay()->toDateString();
        $tillDate = Carbon::now()->addMonth()->startOfDay()->toDateString();
        $countData = InventoryProduct::byArea()->whereBetween('exp_date', [$fromDate, $tillDate])->groupBy('product_id')->count();
        return $this->responseSuccess($countData);
    }

    public function itemQuantity($dir, Request $request) {
        $lang = $request->header('lang');
        $locale = $lang ?? app()->getLocale();
        $q = "product_id,(select name from product_translations where inventory_areas.product_id =product_translations.product_id and locale = '" . $locale . "' limit 1 ) as proudct_name,SUM(total_qty) as total_stock";
        $data = InventoryArea::byArea()->with('product.unit')->selectRaw($q)
                        ->groupBy('product_id')
                        ->orderBy('total_qty', $dir)
                        ->take(5)->get();
        return $this->responseSuccess($data);
    }

    public function categorySoldProducts($dir = 'ASC') {
        $user = auth('admin')->user();
        if ($user) {
            if (!$user->hasRole(['super-admin', 'operation-manager'])) {
                // by user area
                $data = new SoldableCategoryAll(Sold::byArea()->with('soldable')->where('soldable_type', Category::class)->orderBy('sold_count', $dir)->take(5)->get());
            } else {
                // total sold count
                $data = Category::with('translation')->active()->orderBy('sold_count', $dir)->take(5)->get();
            }
        }


        return $this->responseSuccess($data);
    }

    public function soldProductsSorted($dir = 'ASC') {
        $user = auth('admin')->user();
        if ($user) {
            if (!$user->hasRole(['super-admin', 'operation-manager'])) {
                // by user area
                $data = new SoldablePrdouctAll(Sold::byArea()->with('soldable')->where('soldable_type', Product::class)->orderBy('sold_count', $dir)->take(5)->get());
            } else {
                // total sold count
                $data = OrderItem::with('item.unit')->selectRaw("product_id,COUNT(product_id) as total_sold")
                        ->groupBy('product_id')
                        ->orderBy('total_sold', $dir)
                        ->take(5)
                        ->get();
            }
        }

        return $this->responseSuccess($data);
    }

    public function areaOrders($dir = 'ASC') {
        $data = Order::byArea()->with('area')->selectRaw("area_id,COUNT(area_id) as total_orders")
                ->groupBy('area_id')
                ->orderBy('total_orders', $dir)
                ->take(5)
                ->get();
        return $this->responseSuccess($data);
    }

    public function storeOrders($dir = 'ASC') {
        $data = Order::byArea()->with('warehouse')
                ->selectRaw("warehouse_id,COUNT(warehouse_id) as total_orders")
                ->groupBy('warehouse_id')
                ->orderBy('total_orders', $dir)
                ->take(5)
                ->get();
        return $this->responseSuccess($data);
    }

    public function expDateOfItems($dir = 'ASC') {
        $countData = InventoryProduct::byArea()->with('product.unit')
                        ->orderBy('exp_date', $dir)->take(5)->get();

        return $this->responseSuccess($countData);
    }

    public function productVisitsCount($dir = 'ASC') {
        $data = Product::with('unit')
                ->orderBy('visits_count', $dir)
                ->take(5)
                ->get();
        return $this->responseSuccess($data);
    }

    public function productVisitsCountPerCustomer($dir = 'ASC', Request $request) {

        //SELECT product_id , count(DISTINCT customer_id) as visits FROM `customer_products` GROUP by product_id
        $lang = $request->header('lang');
        $locale = $lang ?? app()->getLocale();
        $unitValue = '(select unit_value from products  where products.id =customer_products.product_id limit 1 ) as unit_value';
        $unitName = '(select name from unit_translations  , products
                        where products.unit_id = unit_translations.unit_id
                        and products.id = customer_products.product_id
                        and locale = "' . $locale . '" limit 1) as unit_name';

        $q = "SELECT product_id , (select name from product_translations where customer_products.product_id =product_translations.product_id and locale = '" . $locale . "' limit 1 ) as proudct_name , " . $unitValue . " , " . $unitName . " , count(DISTINCT customer_id) as customer_visits FROM `customer_products` GROUP by product_id order by customer_visits " . $dir . "  limit 5";

        $data = DB::select(DB::raw($q));
        return $this->responseSuccess($data);
    }

    public function itemsOutOfStock(Request $request) {
        $lang = $request->header('lang');
        $locale = $lang ?? app()->getLocale();
        
        $user = auth('admin')->user();
        if ($user) {
            if (!$user->hasRole(['super-admin', 'operation-manager'])) {
                 $adminAreas = implode(',',$user->areas->pluck('id')->toArray());
                // $countData = InventoryArea::byArea()->where('total_qty', 0)->count();
                 $q = "select ia.area_id,at.name,count(product_id) out_of_stock from inventory_areas ia
                                                inner join area_translations at on ia.area_id=at.area_id
                                                where total_qty = 0
                                                and ia.area_id in ('$adminAreas')
                                                and bundle_id is null    
                                                and at.locale = '$locale'
                                                group by ia.area_id";
                 $countData = $data = DB::select(DB::raw($q));  
            }else{
                 $q = "select ia.area_id,at.name,count(product_id) out_of_stock from inventory_areas ia
                                                inner join area_translations at on ia.area_id=at.area_id
                                                where total_qty = 0
                                                and bundle_id is null
                                                and at.locale = '$locale'
                                                group by ia.area_id";
                 $countData = $data = DB::select(DB::raw($q));               
                
            }
        }        
       
        
        return $this->responseSuccess($countData);
    }
    
    public function itemsOutOfStockByArea($id,Request $request){
        $itemOutOfStockInArea = InventoryArea::where('total_qty', 0)->where('area_id',$id)->where('bundle_id',null) ;
        $perPage = $request->has('per_page') ? (int) $request->per_page : 20;
        $pagination = $itemOutOfStockInArea->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);
 
        $data = new InventoryAreaAll($pagination);
        return $this->responsePaginatedSuccess($data, null, $request);
 
    }

    public function avgOrdersPrice(Request $request) {
        $countData = Order::byArea()->avg('final_total');

        return $this->responseSuccess($countData);
    }

    public function validPromotions(Request $request) {
        Carbon::now()->toDateString();
        $countData = Promotion::where('start_validity', '<=', Carbon::now()->toDateString())
                        ->where('end_validity', '>=', Carbon::now()->toDateString())
                        ->where('status', 1)
                        ->orWhere('start_validity', null)->orWhere('end_validity', null)
                        ->count();
        return $this->responseSuccess($countData);
    }

    /**
     * Get Map Data
     */
    public function getMapData(Request $request) {
        
        if (!$request->has('area_id')) {
            $areaID = Area::where('default', '1')->value('id');
        } else {
            $areaID = $request->area_id;
        }

        // Get All Stores
        $stores = Warehouse::byArea()->get();

        // Get Pending Orders
        $pendingOrders = Order::byArea()->where('status', Order::STATUS_PENDING)->get();

        // Get On The Waye Orders
        $ontheWayOrders = Order::byArea()->where('status', Order::STATUS_ON_THE_WAY)->get();

        // Get On The Waye Orders
        $delayedOrders = Order::byArea()->where('status', Order::STATUS_SCHEDULED)->get();

        // Get Delivered Orders
        $deliveredOrders = Order::byArea()->where('status', Order::STATUS_DELIVERED)->get();

        // Get Cancelled Orders
        $cancelledOrders = Order::byArea()->whereIn('status', [Order::STATUS_CANCELLED, Order::STATUS_CANCELLED_FOR_ITEMS])->get();

        // Get Drivers With order
        $deliveryDrivers = Driver::byArea()->where('availability', Driver::AVAILABILITY_DELIVERY)->get();

        // Get Returning back Drivers
        $backDrivers = Driver::byArea()->where('availability', Driver::AVAILABILITY_BACK)->get();

        // Get Online Drivers
        $onlineDrivers = Driver::byArea()->where('area_id', $areaID)->where('availability', Driver::AVAILABILITY_IDLE)->get();

        // Get Break Drivers
        $breakDrivers = Driver::byArea()->where('availability', Driver::AVAILABILITY_BREAK)->get();

        $data['stores'] = new WarehousesResources($stores);

        $data['pending_orders'] = new OrdersResources($pendingOrders);
        $data['on_the_way_orders'] = new OrdersResources($ontheWayOrders);
        $data['delayed_orders'] = new OrdersResources($delayedOrders);
        $data['delivered_orders'] = new OrdersResources($deliveredOrders);
        $data['cancelled_orders'] = new OrdersResources($cancelledOrders);
 
        $data['delivery_drivers'] = new DriversResources($deliveryDrivers);
        $data['back_drivers'] = new DriversResources($backDrivers);
        $data['online_drivers'] = new DriversResources($onlineDrivers);
        $data['break_drivers'] = new DriversResources($breakDrivers);

        return $this->responseSuccess($data);
    }

}
