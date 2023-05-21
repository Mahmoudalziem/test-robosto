<?php

namespace Webkul\Admin\Repositories\PurchaseOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Product\Models\Product;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Models\Config;
use Webkul\Purchase\Contracts\PurchaseOrder;
use Webkul\Supplier\Models\Supplier;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryWarehouse;

class PurchaseOrderRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return 'Webkul\Purchase\Contracts\PurchaseOrder';
    }

    /**
     * Display a listing of the resource.
     *
     * @param $request
     * @return Response
     */
    public function list($request) {
        $query = $this->newQuery();
        $query = $query->byArea();
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
        if (isset($request['supplier_id']) && !empty($request['supplier_id'])) {
            $query->where('supplier_id', $request['supplier_id']);
        }
        if (isset($request['warehouse_id']) && is_array($request['warehouse_id']) && !empty($request['warehouse_id'])) {
            $query->whereIn('warehouse_id', $request['warehouse_id']);
        }
        if (isset($request['is_draft']) && !empty($request['is_draft'])) {
            $query->where('is_draft', $request['is_draft']);
        }
        if (isset($request['from_date']) && isset($request['to_date']) && !empty($request['from_date']) && !empty($request['to_date'])) {
            $query->whereBetween('updated_at', [$request['from_date'] . ' 00:00:00', $request['to_date'] . ' 23:59:59']);
        }

        // if filter by created by or PO no or product in PO
        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->where('purchase_order_no', 'LIKE', '%' . trim($request->filter) . '%')
                    ->orWhereHas('createdBy', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . trim($request->filter) . '%');
                    })
                    ->orWhereHas('products.product', function ($q) use ($request) {
                        $q->whereTranslationLike('name', '%' . $request->filter . '%');
                    })
            ;
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

    /**
     * @param  array  $data
     * @return PurchaseOrder
     */
    public function create(array $data) {
        // 1- Save Purchase Order with basic data [ 'invoice_no', 'is_draft', 'warehouse_id', 'supplier_id']
        $purchaseOrder = $this->model->create($data);

        // Generate Purchase Order Number if Order not Save as Draft
        $purchaseOrder->purchase_order_no = 'PO-' . $purchaseOrder->id . rand(1, 9999);
        $purchaseOrder->save();

        // Start in Cycle and handle Products
        $purchaseOrder = $this->handleProducts($data, $purchaseOrder);

        $this->updatePurchaseOrderWithAmmount($data, $purchaseOrder);

        if ($data['is_draft'] == 0) {
            $this->updateProductsPriceIFInCategory($data['products']);
        }

        return $purchaseOrder;
    }

    private function handleProducts(array $data, $purchaseOrder) {
        // Get Supplier
        $supplier = Supplier::find($data['supplier_id']);

        foreach ($data['products'] as $item) {
            // Get Product
            $product = Product::findOrFail($item['id']);

            // Check that each product exist in supplier_products, if not exist -> then Insert this product to supplier
            $this->checkProductExistWithSUpplier($supplier, $product);

            // Prepare Product Data
            $productSKUData = $this->prepareProductData($data, $item, $product);

            // Insert in purchase_order_products
            $draftedProduct = $purchaseOrder->products()->create($productSKUData);

            // Save as Draft if will not Insert in Inventory_products
            if ($data['is_draft'] == 0) {
                // Insert in inventory_products
                InventoryProduct::create($productSKUData);

                // 5- Update inventory_warehouses table with new quantity for given warehouse
                $this->increaseInventoryWarehouse($data, $product, $draftedProduct->qty);

                // 6- Update inventory_areas table with new quantity for given warehouse area
                $this->increaseInventoryArea($data, $product, $draftedProduct->qty);
            }
        }
        return $purchaseOrder;
    }

    /**
     * Prepare Product Data
     *
     * @param array $data
     * @param array $item
     * @param $product
     * @return array
     */
    public function prepareProductData($data, $item, $product) {
        $sku = $this->generateSKU($product);

        // Calculate Discount Percentage
        $costAfterDiscount = $this->calculateDiscountPercentage($item, $data);

        $productSKUData = [
            'sku' => $sku,
            'prod_date' => $item['prod_date'],
            'exp_date' => $item['exp_date'],
            'qty' => $item['qty'],
            'cost_before_discount' => $item['cost'],
            'cost' => $costAfterDiscount,
            'amount_before_discount' => $item['cost'] * $item['qty'],
            'amount' => $costAfterDiscount * $item['qty'],
            'product_id' => $product->id,
            'warehouse_id' => $data['warehouse_id'],
            'area_id' => $data['area_id'],
        ];

        return $productSKUData;
    }

    /**
     * Update Invetory Warehouse
     *
     * @param $data
     * @param $product
     * @param $qty
     * @return void
     */
    public function increaseInventoryWarehouse($data, $product, $qty) {
        $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $product->id)->where('warehouse_id', $data['warehouse_id'])->where('area_id', $data['area_id'])->first();

        if (!$productInInventoryWarehouse) {
            $productInInventoryWarehouse = InventoryWarehouse::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $data['warehouse_id'],
                        'area_id' => $data['area_id'],
            ]);
        }

        $productInInventoryWarehouse->qty = $productInInventoryWarehouse->qty + $qty;
        $productInInventoryWarehouse->save();
    }

    /**
     * Update Invetory Warehouse
     *
     * @param $data
     * @param $product
     * @param $qty
     * @return void
     */
    public function increaseInventoryArea($data, $product, $qty) {
        $productInInventoryArea = InventoryArea::where('product_id', $product->id)->where('area_id', $data['area_id'])->first();

        if (!$productInInventoryArea) {
            $productInInventoryArea = InventoryArea::create([
                        'product_id' => $product->id,
                        'area_id' => $data['area_id'],
                        'init_total_qty' => $qty,
            ]);
        }

        $productInInventoryArea->total_qty = $productInInventoryArea->total_qty + $qty;
        $productInInventoryArea->save();
    }

    /**
     * Show the specified purchaseOrder.
     *
     * @param  int  $id
     * @return Response
     */
    public function updatePurchaseOrderWithAmmount($data, $purchaseOrder) {

        // 4- Save Extra Data to Purchase Order ['sub_total', 'discount', 'total']
        $purchaseOrderTotalBeforeDiscount = $purchaseOrder->products()->get()->sum(function ($q) {
            return $q->cost_before_discount * $q->qty;
        });
        $purchaseOrderTotalAfterDiscount = $purchaseOrder->products()->get()->sum('amount');

        $purchaseOrder->sub_total_cost = $purchaseOrderTotalBeforeDiscount;
        $purchaseOrder->discount_type = $data['discount_type'] ?? null;
        $purchaseOrder->discount = $data['discount'] ?? null;
        $purchaseOrder->total_cost = $purchaseOrderTotalAfterDiscount;
        $purchaseOrder->save();

        return $purchaseOrder;
    }

    /**
     * Show the specified purchaseOrder.
     *
     * @param int $id
     * @return Builder|Builder[]|Collection|\Illuminate\Database\Eloquent\Model
     */
    public function show($id) {
        return $this->model->with(['supplier:id,name', 'warehouse:id', 'products.product:id'])->findOrFail($id);
    }

    /**
     * Show the specified purchaseOrder.
     *
     * @param  mixed  $purchaseOrder
     * @return Response
     */
    public function update($purchaseOrder, $data) {

        // Update Purchase Order Table
        $purchaseOrder->update($data);

        // First Delete Old Products
        $purchaseOrder->products()->delete();

        // Handle Products to Save them
        $purchaseOrder = $this->handleProducts($data, $purchaseOrder);

        // Update Purchase Order Table
        $this->updatePurchaseOrderWithAmmount($data, $purchaseOrder);

        return $purchaseOrder;
    }

    /**
     * Show the specified purchaseOrder.
     *
     * @param  mixed  $purchaseOrder
     * @return Response
     */
    public function updateDraftToIssued($purchaseOrder, $admin_id) {

        // Get Purchase Order
        $purchaseOrder->is_draft = 0;
        $purchaseOrder->admin_id = $admin_id;
        $purchaseOrder->save();

        $products = $this->prepareDataWhenUpdateToIssued($purchaseOrder->products);
        $data = [
            "is_draft" => 0,
            "warehouse_id" => $purchaseOrder->warehouse_id,
            "area_id" => $purchaseOrder->area_id,
            "supplier_id" => $purchaseOrder->supplier_id,
            'discount_type' => $purchaseOrder->discount_type,
            'discount' => $purchaseOrder->discount,
            "products" => $products
        ];

        // First Delete Old Products
        $purchaseOrder->products()->delete();

        // Handle Products to Save them
        $purchaseOrder = $this->handleProducts($data, $purchaseOrder);

        // Update Purchase Order Table
        $this->updatePurchaseOrderWithAmmount($data, $purchaseOrder);

        $this->updateProductsPriceIFInCategory($data['products']);
        return $purchaseOrder;
    }

    private function updateProductsPriceIFInCategory($products){
        $pIDS = collect(DB::select(DB::raw("SELECT product_id FROM product_sub_categories where sub_category_id in(39,40);")))->pluck('product_id')->toArray();
        foreach ($products as $product) {
            if(in_array($product['id'],$pIDS)){
                 Product::where('id',$product['id'])->update([
                    'price'=>( $product['cost'] +  ( $product['cost'] * 0.20 ) )
                 ]);
                 Log::info('product id :' .$product['id'] . ' should be '.( $product['cost'] +  ( $product['cost'] * 0.20 ) ) );
            }
        }
    }

    /**
     * Show the specified purchaseOrder.
     *
     * @param  mixed  $purchaseOrder
     * @return Response
     */
    public function updateDraftToCancelled($purchaseOrder, $admin_id) {

        // Get Purchase Order
        $purchaseOrder->is_draft = 2;
        $purchaseOrder->admin_id = $admin_id;
        $purchaseOrder->save();

        return $purchaseOrder;
    }

    /**
     * Prepare Product Data
     *
     * @param array $data
     * @param array $item
     * @param $product
     * @return array
     */
    public function prepareDataWhenUpdateToIssued($products) {
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                "id" => $product->product_id,
                "qty" => $product->qty,
                "cost" => $product->cost,
                "prod_date" => $product->prod_date,
                "exp_date" => $product->exp_date
            ];
        }
        return $data;
    }

    /**
     * Check that each product exist in supplier_products
     */
    private function checkProductExistWithSUpplier($supplier, $product) {
        $checkExistance = $supplier->products()->where('product_id', $product->id)->first();
        if (!$checkExistance) {
            $supplier->products()->attach($product->id, ['brand_id' => $product->brand_id]);
        }
    }

    /**
     * Calculate Discount Percentage
     * @param $item
     * @param $data
     * @return float|int
     */
    private function calculateDiscountPercentage($item, $data) {
        // if discount less than ZERo or Equal, DO nothing
        if (empty($data['discount']) || $data['discount'] <= 0) {
            return $item['cost'];
        }

        $discountType = $data['discount_type'];
        $discountValue = $data['discount'];
        $itemsCount = count($data['products']);

        // if Discount Type in percentage, then transform into Money
        if ($discountType == 'per') {
            $percentage = $discountValue / 100; // 3 / 100 = .03
            $discountValue = $percentage * $item['cost'];   // .03 * 10 = 0.3
            return $item['cost'] - $discountValue;  // 10 - .3 = 9.7
        }

        // Else, in Money
        $discountForAll = ($discountValue / $itemsCount);   // 9 / 3 = 6
        $discountOnAmount = ($item['qty'] * $item['cost']) - $discountForAll; // (5*10) - 3 = 47
        return $discountOnAmount / $item['qty'];  // 47 / 5 = 9.4
    }

    public function warehousesSearch($request) {
        $query = Warehouse::query();
        if (isset($request['q'])) {
            $query->whereTranslationLike('name', '%' . $request['q'] . '%');
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

    /**
     * Generate SKU
     */
    private function generateSKU(Product $product) {
        $brand = $product->brand ? $product->brand->prefix : 'BR';

        $prefix = $product->prefix ?? 'PR';

        $lastNumber = 1;

        // Get Last Auto Increment from Config
        $lastSkuFromConfig = Config::where('key', Config::LAST_SKU_NUMBER)->first();

        if ($lastSkuFromConfig) {
            $lastNumber = str_pad($lastSkuFromConfig->value, 4, '0', STR_PAD_LEFT);

            // Update Config Last Number
            Config::where('key', Config::LAST_SKU_NUMBER)->update([
                'value' => (int) $lastSkuFromConfig->value + 1
            ]);
        }

        return $brand . $prefix . $lastNumber;
    }
    public function getPOFullInfo($id){

        $allPOInfo = "SELECT
                            *
                        FROM
                            purchase_order_products
                        WHERE
                            purchase_order_id = {$id}";
        $allPOInfo = preg_replace("/\r|\n/", "", $allPOInfo);
        $allPOInfoQuery = collect(DB::select(DB::raw($allPOInfo)));


        $allSkus = $allPOInfoQuery->pluck("sku")->toArray();
        // dd($allPOInfoQuery->pluck("sku"));
        foreach($allSkus as $key => $sku) {
            $allSkus[$key] = "'".$sku."'";
        }
        $implodedSKUs = implode(', ', $allSkus);
        $soldAndQuantity = "SELECT
        T.sku,
        SUM(T.qty) AS 'sold_q',
        SUM(order_items.base_total) AS 'sold_price'
        FROM
            (SELECT
                *
            FROM
                order_item_skus
            WHERE
                sku IN ({$implodedSKUs})) T
                INNER JOIN
            orders ON T.order_id = orders.id
                INNER JOIN
            order_items ON T.order_item_id = order_items.id
        WHERE
        status = 'delivered'
        GROUP BY T.sku";
        $soldAndQuantity = preg_replace("/\r|\n/", "", $soldAndQuantity);
        $soldAndQuantityQuery = collect(DB::select(DB::raw($soldAndQuantity)));

        $adjustedUp = "SELECT
        sku, sum(qty) as up_quantity
        FROM
            (SELECT
            *
            FROM
                inventory_adjustment_products
            WHERE
                sku IN ({$implodedSKUs})) T
            INNER JOIN
                inventory_adjustments ON inventory_adjustments.id = T.inventory_adjustment_id
            WHERE
                T.status IN ('3' , '5')
            AND inventory_adjustments.status = '2'  GROUP BY T.sku;";
        $adjustedUp = preg_replace("/\r|\n/", "", $adjustedUp);
        $adjustedUpQuery = collect(DB::select(DB::raw($adjustedUp)));


         $adjustedDown = "SELECT
         sku, sum(qty) as down_quantity
         FROM
             (SELECT
             *
             FROM
                 inventory_adjustment_products
             WHERE
                 sku IN ({$implodedSKUs})) T
             INNER JOIN
                 inventory_adjustments ON inventory_adjustments.id = T.inventory_adjustment_id
             WHERE
                 T.status IN ('1' , '2' , '4')
             AND inventory_adjustments.status = '2'  GROUP BY T.sku;";
         $adjustedDown = preg_replace("/\r|\n/", "", $adjustedDown);
         $adjustedDownQuery = collect(DB::select(DB::raw($adjustedDown)));
         $fianlArray = [];
         foreach($allPOInfoQuery as $row){
            $row->sold_quantity = 0;
            $row->sold_price = 0;
            $row->adjusted_up =0;
            $row->adjusted_down = 0;
            $fianlArray["$row->sku"] = $row;
         }

         foreach($soldAndQuantityQuery as $row){
             $rowToAddValues = $fianlArray[$row->sku];
             $rowToAddValues->sold_quantity = $row->sold_q;
             $rowToAddValues->sold_price = $row->sold_price;
             $fianlArray[$row->sku] = $rowToAddValues;
         }

         foreach($adjustedUpQuery as $row){
            $rowToAddValues = $fianlArray[$row->sku];
            $rowToAddValues->adjusted_up = $row->up_quantity;
            $fianlArray[$row->sku] = $rowToAddValues;
        }
        foreach($adjustedDownQuery as $row){
            $rowToAddValues = $fianlArray[$row->sku];
            $rowToAddValues->adjusted_down = $row->down_quantity;
            $fianlArray[$row->sku] = $rowToAddValues;
        }
        return collect(array_values($fianlArray));
    }

}
