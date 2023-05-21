<?php

namespace Webkul\Admin\Repositories\Product;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Purchase\Models\PurchaseOrderProduct;

class ProductRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return 'Webkul\Product\Contracts\Product';
    }

    /**
     * @param $request
     * @return \Webkul\Product\Contracts\Product
     */
    public function list($request) {

        $query = $this->newQuery();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multi-sort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        // Search by SubCategory
        if ($request->exists('sub_category_id') && !empty($request['sub_category_id'])) {
            $query->whereHas('subCategories', function(Builder $query) use ($request) {
                $query->where('sub_category_id', $request['sub_category_id']);
            });
        }

        // Search by Category
        if ($request->exists('category_id') && !empty($request['category_id'])) {
            $query->whereHas('subCategories', function(Builder $query) use ($request) {
                $query->whereHas('parentCategories', function(Builder $query) use ($request) {
                    $query->where('category_id', $request['category_id']);
                });
            });
        }

        // Search by Warehouse
        if ($request->exists('warehouses') && !empty($request['warehouses'])) {
            $query->whereHas('warehouses', function(Builder $query) use ($request) {
                $query->whereIn('warehouse_id', json_decode($request['warehouses']));
            });
        }

        // Search by Brand
        if ($request->exists('brand_id') && !empty($request['brand_id'])) {
            $query->where('brand_id', $request['brand_id']);
        }
        
        // Search by Status
        if ($request->exists('status') && ($request['status'] != null)) {
            $query->where('status', $request['status']);
        }
                

        // if filter
        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->whereTranslationLike('name', '%' . $request->filter . '%')
                    ->orWhere('barcode', trim($request->filter));
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
     * @return \Webkul\Product\Contracts\Product
     */
    public function create(array $data) {

        $product = $this->model->create($data);

        // Store image
        $this->saveImgBase64($data, $product, 'image', true);

        // Save Sub Categories
        $product->subCategories()->sync($data['sub_categories']);

        // Save Tags
        if(isset($data['tags']) && !empty($data['tags'])){
            $product->tags()->sync($data['tags']);        
        }
        
        
        return $product;
    }

    /**
     * @param  array  $data
     * @param  mixed  $product
     * @param  string  $attribute
     * @return \Webkul\Product\Contracts\Product
     */
    public function update(array $data, $product, $attribute = "id") {
        
       $product->update($data);

        // Store image
        if (isset($data['image'])) {
            $this->saveImgBase64($data, $product, 'image', true);
        }

        // Save Sub Categories
        if (isset($data['sub_categories'])) {
            $product->subCategories()->sync($data['sub_categories']);
        }
        
        // Save Tags
        if(isset($data['tags']) && !empty($data['tags'])){
            $product->tags()->sync($data['tags']);        
        }
                

        return $product;
    }

    /**
     * @param $data
     * @param int $id
     * @return mixed
     */
    public function getProductSku($data, int $id) {
        $skus = InventoryProduct::where('product_id', $id)->latest();
        // Search by Brand
        if (isset($data['warehouses']) && !empty($data['warehouses'])) {
            $skus->whereIn('warehouse_id', json_decode($data['warehouses']));
        }
        return $skus->get();
    }

    /**
     * @param $data
     * @param int $id
     * @return mixed
     */
    public function getSupplierBySku($sku) {
        $purchaseOrderProduct = PurchaseOrderProduct::where('sku', $sku)->first();
        
        $supplier = $purchaseOrderProduct ? $purchaseOrderProduct->purchaseOrder->supplier : null;
        if ($supplier)
            return ['id' => $supplier->id, 'name' => $supplier->name];
        else
            return null;
    }

    /**
     * @param  int  $id
     * @return void
     */
    public function delete($id) {
        parent::delete($id);
    }

    public function listProductsByBrandID($request) {
        $query = $this->newQuery();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'asc');
        }
        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->where('brand_id', $request['brand_id'])->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

}
