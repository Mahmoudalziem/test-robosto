<?php

namespace Webkul\Admin\Repositories\Bundle;

use Webkul\Bundle\Models\Bundle;
use Webkul\Product\Models\Product;
use Webkul\Core\Eloquent\Repository;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Area\Models\Area;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BundleRepository extends Repository {

    protected $bundlePrice = 0;
    protected $orginalPrice = 0;
    protected $bundleWeight = 0;
    protected $bundleSubCategory = [];

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return 'Webkul\Bundle\Contracts\Bundle';
    }

    /**
     * @param $request
     * @return \Webkul\Bundle\Contracts\Bundle
     */
    public function list($request) {

        $query = $this->newQuery();

        // Search by areas
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->where('area_id', $request['area_id']);
        }
        
        // Search by Status
        if ($request->exists('status') && ( ($request['status'] != null) || ($request['status'] != '') )) {
            $query->where('status', $request['status']);
        }        

        // if filter
        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->whereTranslationLike('name', '%' . $request->filter . '%');
        }
        $query->orderBy('id', 'desc');
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
     * @return \Webkul\Bundle\Contracts\Bundle
     */
    public function create(array $data) {



        $data['area_id'] = $data['areas'][0];

        // First Create Bundle
        $bundle = $this->model->create($data);

        // assgin bundle to areas
        $bundle->areas()->sync($data['areas']);

        // Calculate and Apply Discount on given products
        $this->handleBundleItems($bundle, $data['items']);

        // update Master Bundle with calculations
        $this->updateBundleTotalPrice($bundle);

        // Store image
        $this->saveImgBase64($data, $bundle, 'image', true);

        // create product as bundle
        $product = $this->createProductAsBundle($bundle, $data);

        // add bundle to area and warehouse
        $this->assignBundleProductToAreaAndWarehouse($bundle, $product, $data);

        return $bundle;
    }

    public function createProductAsBundle($bundle, $data) {
        //Log::info($data);
        $save = [
            'ar' => [
                'name' => $bundle->translate('ar')->name,
                'description' => $bundle->translate('ar')->description
            ],
            'en' => [
                'name' => $bundle->translate('en')->name,
                'description' => $bundle->translate('en')->description
            ],            
            'barcode' => $this->generateBarcodeNumber(),
            'brand_id' => 1,
            'unit_id' => 1,
            'unit_value' => 1,
            'bundle_id' => $bundle->id,
            'featured' => 1,
            'status' => 1,
            'returnable' => 1,
            'cost' => $this->getOrginalPrice(),
            'price' => $this->getBundlePrice(),
            'weight' => $this->getBundleWeight(),
            'width' => 1,
            'height' => 1,
            'length' => 1,
            'prefix' => "BU",
            'shelve_id' => 1,
            'note' => "Nwe Bundle",
        ];
        $product = Product::where('bundle_id', $bundle->id)->first();
        if ($product) {
            $product->update($save);
        } else {

            $product = Product::create($save);
        }

        // Store image
        if (isset($data['image']) && !empty($data['image'])) {
            $this->saveImgBase64($data, $product, 'image', true);
        }

        // Save Sub Categories
        // assign product to requested subcategories 
        // else get getBundelSubCategory
        $subCategories = isset($data['subcategories']) && !empty($data['subcategories']) ? $data['subcategories'] : $this->getBundelSubCategory();
        $product->subCategories()->sync($subCategories);
        return $product;
    }

    public function assignBundleProductToAreaAndWarehouse($bundle, $product, $data) {
        InventoryArea::where('bundle_id', $bundle->id)->delete();
        InventoryWarehouse::where('bundle_id', $bundle->id)->delete();
        foreach ($data['areas'] as $areaId) {
            InventoryArea::create([
                'area_id' => $areaId,
                'product_id' => $product->id,
                'bundle_id' => $bundle->id,
                'init_total_qty' => 1,
                'total_qty' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $area = Area::find($areaId);
            $warehouses = $area->warehouses;
            foreach ($warehouses as $warehouse) {
                InventoryWarehouse::create([
                    'area_id' => $areaId,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'bundle_id' => $bundle->id,
                    'qty' => 1,
                    'can_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * @param Bundle $bundle
     * @param array $items
     * @return mixed
     */
    public function handleBundleItems(Bundle $bundle, $items) {
        $itemsIDs = array_column($items, 'id');
        $productsFromDB = Product::whereIn('id', $itemsIDs)->get();

        $totalProductsPrice = $this->calculateTotalProductsPrice($items, $productsFromDB);

        $discountPercentage = $this->getDiscountValueInPercentage($bundle, $totalProductsPrice);
        $bundleWeight = 0;
        $subCategory = [];
        // Save bundle Items
        foreach ($items as $item) {
            $product = $productsFromDB->find($item['id']);

            $totalOriginalPrice = $item['qty'] * $product->price;

            // Get Price afte rapply Bundle Discount
            $totalBundlePrice = $this->calculateDiscountForProduct($totalOriginalPrice, $discountPercentage);
            $bundlePrice = $this->calculateDiscountForProduct($product->price, $discountPercentage);
            $bundleWeight += ($product->weight * $item['qty']);
            $subCategory = array_merge($subCategory, $product->subCategories->pluck('id')->toArray());
            $itemData = [
                'product_id' => $product->id,
                'quantity' => $item['qty'],
                'original_price' => $product->price,
                'bundle_price' => $bundlePrice,
                'total_original_price' => $totalOriginalPrice,
                'total_bundle_price' => $totalBundlePrice,
            ];

            // Save New Item
            $bundle->items()->create($itemData);
        }
        $this->setBundelSubCategory(array_unique($subCategory));

        $this->setBundleWeight($bundleWeight);
    }

    /**
     * @param float $price
     * @param float $discountPercentage
     * 
     * @return float
     */
    protected function calculateDiscountForProduct(float $price, float $discountPercentage) {
        //return $price * ($discountPercentage / 100);
        return $price - (($discountPercentage / 100) * $price);
    }

    /**
     * @param array $items
     * @return mixed
     */
    protected function calculateTotalProductsPrice(array $items, $productsFromDB) {
        $totalPrice = 0;
        foreach ($items as $item) {

            $product = $productsFromDB->find($item['id']);

            $totalPrice += $item['qty'] * $product->price;
        }

        return $totalPrice;
    }

    /**
     * @param Bundle $bundle
     * @param int $itemsCount
     * @return mixed
     */
    protected function getDiscountValueInPercentage(Bundle $bundle, float $totalPrice) {

        if ($bundle->discount_type == Bundle::DISCOUNT_TYPE_VALUE) {
            return (($bundle->discount_value / $totalPrice) * 100);
        } else {
            return $bundle->discount_value;
        }
    }

    /**
     * @param Bundle $bundle
     * @return void
     */
    protected function updateBundleTotalPrice(Bundle $bundle) {
        $items = $bundle->items()->get();

        $bundle->total_original_price = $items->sum('total_original_price');
        $bundle->total_bundle_price = $items->sum('total_bundle_price');
        $this->setOrginalPrice($bundle->total_original_price);
        $this->setBundlePrice($bundle->total_bundle_price);
        $bundle->save();
    }

    protected function setBundlePrice($price) {
        $this->bundlePrice = $price;
    }

    protected function getBundlePrice() {
        return $this->bundlePrice;
    }

    protected function setOrginalPrice($price) {
        $this->orginalPrice = $price;
    }

    protected function getOrginalPrice() {
        return $this->orginalPrice;
    }

    protected function setBundleWeight($weight) {
        $this->bundleWeight = $weight;
    }

    protected function getBundleWeight() {
        return $this->bundleWeight;
    }

    protected function setBundelSubCategory($subCategory) {
        $this->bundleSubCategory = $subCategory;
    }

    protected function getBundelSubCategory() {
        return $this->bundleSubCategory;
    }

    protected function generateBarcodeNumber() {
        $number = mt_rand(1000000000000, 9999999999999); // better than rand()
        // call the same function if the barcode exists already
        if ($this->barcodeNumberExists($number)) {
            return $this->generateBarcodeNumber();
        }

        // otherwise, it's valid and can be used
        return $number;
    }

    protected function barcodeNumberExists($number) {
        // query the database and return a boolean
        return Product::where('barcode', $number)->exists();
    }

    /**
     * @param  array  $data
     * @param  mixed  $bundle
     * @param  string  $attribute
     * @return \Webkul\Bundle\Contracts\Bundle
     */
    public function update(array $data, $bundle, $attribute = "id") {


        // First Delete Old Products
        $bundle->items()->delete();

        // then update with new data
        $bundle->update($data);

        // assgin bundle to areas
        $bundle->areas()->sync($data['areas']);

        // Calculate and Apply Discount on given products
        $this->handleBundleItems($bundle, $data['items']);

        // update Master Bundle with calculations
        $this->updateBundleTotalPrice($bundle);

        // Store image
        if (isset($data['image']) && !empty($data['image'])) {
            $this->saveImgBase64($data, $bundle, 'image', true);
        }

        // create product as bundle
        $product = $this->createProductAsBundle($bundle, $data);

        // add bundle to area and warehouse
        $this->assignBundleProductToAreaAndWarehouse($bundle, $product, $data);

        return $bundle;
    }

    /**
     * @param  array  $data
     * @param  mixed  $bundle
     * @param  string  $attribute
     * @return \Webkul\Bundle\Contracts\Bundle
     */
    public function updateStatus(array $data, $bundle, $attribute = "id") {

        $bundle->status = $data['status'];
        $bundle->save();
        // deactive bundle product
        if ($bundle->status == 0) {

            $bundle->product()->where('bundle_id', $bundle->id)->update(['status' => $data['status']]);

            // inventory area == 0
            foreach ($bundle->areas()->pluck('area_id') as $areaId) {
                InventoryArea::where([
                    'area_id' => $areaId,
                    'bundle_id' => $bundle->id,
                ])->update(['total_qty' => 0]);
 
                InventoryWarehouse::where([
                    'area_id' => $areaId,
                    'bundle_id' => $bundle->id,
                ])->update(['qty' => 0]);
            }
        } else {  // activate bundle product
            $bundle->product()->where('bundle_id', $bundle->id)->update(['status' => $data['status']]);

            $this->updateBundleQtyInAreaAndWarehouse($bundle);
        }
        return $bundle;
    }

    /**
     * @param  int  $id
     * @return void
     */
    public function delete($bundle) {

        foreach ($bundle->areas()->get() as $area) {
            // delete from inventory werehouse
            foreach ($area->warehouses()->get() as $warehouse) {
                InventoryWarehouse::where('warehouse_id', $warehouse->id)->where('bundle_id', $bundle->id)->delete();
            }
            // delete from inventory area
            InventoryArea::where('area_id', $area->id)->where('bundle_id', $bundle->id)->delete();
        }

        // delete from area
        $bundle->areas()->sync([]);
        // delete product
        if ($bundle->product) {
            if ($bundle->product->image) {
                Storage::delete($bundle->product->image);
            }
            $bundle->product->delete;
        }

        // delete from bundle items
        if ($bundle->items) {
            $bundle->items()->delete();
        }

        // delete from bundle
        if ($bundle->image) {
            Storage::delete($bundle->image);
        }

        parent::delete($bundle->id);
    }

    public function updateBundleQtyInAreaAndWarehouse($bundle) {

        $productBundleItems = $bundle->items;

        foreach ($bundle->areas as $area) {
            // check qty stock in stock for product that is bundle
            $qtyInStock = [];
            foreach ($productBundleItems as $item) { // item in product bundle
                $invAreay = InventoryArea::where(['product_id' => $item['product_id'], 'area_id' => $area->id])->first();
                if ($invAreay) {
                    $invQty = $invAreay->total_qty;
                    $bundleQty = $item->quantity;
                    $qty = $invQty > 0 ? $invQty / $bundleQty : 0; // 15 / 4 = 3.75 = 3

                    array_push($qtyInStock, intval($qty));
                } else {
                    array_push($qtyInStock, 0);
                }
            }
            $totalInStock = min($qtyInStock);
            Log::info('$bundle: ' . $bundle->id . ' in area: ' . $area->id . '  === totalInStock: ' . $totalInStock);
            if ($totalInStock < 1) {

                // Set total_qty = 0 in area
                $productInInventoryArea = InventoryArea::where('bundle_id', $bundle->id)->where('area_id', $area->id)->first();
                if ($productInInventoryArea) {
                    $productInInventoryArea->total_qty = 0;
                    $productInInventoryArea->save();
                }

                // inventory warehouse == 0            
                $warehouses = $area->warehouses;
                foreach ($warehouses as $warehouse) {

                    // Set qty = 0 in warehouse
                    $productInInventoryWarehouse = InventoryWarehouse::where('bundle_id', $bundle->id)->where('warehouse_id', $warehouse->id)->where('area_id', $area->id)->first();
                    if ($productInInventoryWarehouse) {
                        $productInInventoryWarehouse->qty = 0;
                        $productInInventoryWarehouse->save();
                    }
                }
            } else {
                // Set total_qty = 1 in area
                $productInInventoryArea = InventoryArea::where('bundle_id', $bundle->id)->where('area_id', $area->id)->first();
                if ($productInInventoryArea) {
                    $productInInventoryArea->total_qty = 1;
                    $productInInventoryArea->save();
                }
                // inventory warehouse == 1    
                $warehouses = $area->warehouses;
                foreach ($warehouses as $warehouse) {
                    // Set qty = 1 in warehouse
                    $productInInventoryWarehouse = InventoryWarehouse::where('bundle_id', $bundle->id)->where('warehouse_id', $warehouse->id)->where('area_id', $area->id)->first();
                    if ($productInInventoryWarehouse) {
                        $productInInventoryWarehouse->qty = 1;
                        $productInInventoryWarehouse->save();
                    }
                }
            }
        }
    }

}
