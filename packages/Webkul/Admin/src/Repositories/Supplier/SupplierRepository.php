<?php

namespace Webkul\Admin\Repositories\Supplier;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;

class SupplierRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Supplier\Contracts\Supplier';
    }

    public function list($request){
        $query = $this->newQuery();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort) ) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }
        
        // Search by Status
        if ($request->exists('status') && ($request['status'] != null)) {
            $query->where('status', $request['status']);
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
     * @return \Webkul\Supplier\Contracts\Supplier
     */
    public function create(array $data)
    {
        $supplier = $this->model->create($data);

        // Save Areas
        $supplier->areas()->sync($data['areas']);

        // Save Products and Brands
        $this->saveSupplierProductsAndBrands($supplier, $data['products']);

        return $supplier;
    }

    /**
     * Show the specified supplier.
     *
     * @param $products
     * @return array
     */
    public function prepareSupplierProductsForUpdate($products)
    {
        $preparedProducts = [];
        foreach ($products as $product) {
            $preparedProducts[] = [
                'brand' => [
                    'id'    =>  $product->brand->id,
                    'name'    =>  $product->brand->name,
                    'image'    =>  $product->brand->image,
                    'image_url'    =>  $product->brand->image_url,
                ],
                'product' => [
                    'id'    =>  $product->id,
                    'name'    =>  $product->name,
                    'image'    =>  $product->image,
                    'image_url'    =>  $product->image_url,
                    'weight'    =>  $product->weight,
                    'unit'    =>  $product->unit,
                ],
            ];
        }
        return $preparedProducts;
    }

    /**
     * @param  array  $data
     * @param  mixed  $supplier
     * @param  string  $attribute
     * @return \Webkul\Supplier\Contracts\Supplier
     */
    public function update(array $data, $supplier, $attribute = "id")
    {
        $supplier->update($data);

        // Save Areas
        if (isset($data['areas'])) {
            $supplier->areas()->sync($data['areas']);
        }

        // Save Products and Brands
        if (isset($data['products'])) {
            $this->saveSupplierProductsAndBrands($supplier, $data['products']);
        }

        return $supplier;
    }

    /**
     * @param    $supplier
     * @param array $products
     * @return void
     */
    public function saveSupplierProductsAndBrands($supplier, $products)
    {
        $data = [];
        // Loop through products of Objects to transform them to Sync Format for M-to-M relation
        // Format must be like [ 1 =>  ['brand_id  =>  3], 2 =>  ['brand_id  =>  5] ]
        foreach ($products as $product) {
            $data[$product['product_id']] = ['brand_id'   =>  $product['brand_id']];
        }

        // Finally Sync Data
        $supplier->products()->syncWithoutDetaching($data);
    }
 

    /**
     * @param  int  $id
     * @return void
     */
    public function delete($id)
    {
        parent::delete($id);
    }
}