<?php

namespace Webkul\Admin\Repositories\Discount;

use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Discount\Models\Discount;
use Webkul\Product\Models\Productlabel;

class DiscountRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return 'Webkul\Discount\Contracts\Discount';
    }

    public function list($request) {
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
            $query = $query->orderBy('id', 'desc');
        }

        if ($request->exists('from_date') && !empty($request['from_date']) && $request->exists('to_date') && !empty($request['to_date'])) {
            $query->where(function ($q) use ($request) {
                $startValidity = $request['from_date'] . ' 00:00:00';
                $endValidity = $request['to_date'] . ' 23:59:59';

                $q->where([['start_validity', '>=', $startValidity], ['end_validity', '<=', $startValidity]])
                        ->orwhereBetween('start_validity', array($startValidity, $endValidity))
                        ->orWhereBetween('end_validity', array($startValidity, $endValidity));
            });
        }

        if ($request->exists('filter') && (!empty($request['filter']) || $request['filter'] == 0 || $request['filter'] != '' )) {
            $query->where(function ($query) use ($request) {
                if (is_numeric($request['filter'])) {
                    $query->where('discount_qty', (int) $request['filter']); // search only with whole number
                }

                $query->orWhere(function ($q) use ($request) {
                    $q->whereHas('product', function (Builder $q) use ($request) {
                        $products = DB::table('product_translations')->select('product_id')
                                        ->where('name', 'like', '%' . $request['filter'] . '%')
                                        ->pluck('product_id')->toArray();

                        $q->whereIn('product_id', $products);
                    });
                });
                $query->orWhere(function ($q) use ($request) {
                    $q->whereHas('areas', function (Builder $q) use ($request) {
                        $areas = DB::table('area_translations')->select('area_id')
                                        ->where('name', 'like', '%' . $request['filter'] . '%')
                                        ->pluck('area_id')->toArray();

                        $q->whereIn('area_id', $areas);
                    });
                });
            });
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
     * @return User
     */
    public function create(array $data) {

 
        $product = Product::find($data['product_id']);
        $json = [];
        // discount areas

        $data['orginal_price'] = $product->price;
        $data['discount_price'] = $data['discount_type'] == 'val' ?
                $product->price - $data['discount_value'] :
                $product->price - $product->price * ( $data['discount_value'] / 100 );

        $areas = $data['area_id']; // multi 
        unset($data['area_id']);
        foreach ($areas as $area) {
            $data['area_id'] = $area; // only one area_id
            $json[$area] = $data;
        }

        unset($data['area_id']); // remove only one area_id

        $discount = $this->model->create($data);
        $discount->areas()->sync($areas);

        $product->discount_details = $json;

        if ($data['label_ckecked'] == 1) {
            // make new label

            if ($data['discount_type'] == 'val') {
                $label['slug'] = Discount::DISCOUNT_VAL;
                $label['ar'] = ['name' => $data['discount_value'] . ' ' . __('admin::app.off', [], 'ar')];
                $label['en'] = ['name' => $data['discount_value'] . ' ' . __('admin::app.off', [], 'en')];
            } else {
                $label['slug'] = Discount::DISCOUNT_PER;
                $label['ar'] = ['name' => $data['discount_value'] . '%'];
                $label['en'] = ['name' => $data['discount_value'] . '%'];
            }

            // check if label exists in  label table
            $labelObj = Productlabel::where('slug', $label['slug'])->first();
            if (!$labelObj) {
                // create new label 
                $label['status'] = 1;
                $labelObj = Productlabel::create($label);
            }

            // assign new label to the product
            $product->productlabel_id = $labelObj->id;
        }

        $product->save();
        return $discount;
    }

    /**
     * @param  array  $data
     * @param  mixed  $discount
     * @param  string  $attribute
     * @return User
     */
    public function update(array $data, $discount, $attribute = "id") {

        $product = Product::find($data['product_id']);
        $json = [];

        // discount update signle area
        $data['orginal_price'] = $product->price;
        $data['discount_price'] = $data['discount_type'] == 'val' ?
                $product->price - $data['discount_value'] :
                $product->price - $product->price * ( $data['discount_value'] / 100 );

        $areas = $data['area_id']; // multi 
        unset($data['area_id']);
        foreach ($areas as $area) {
            $data['area_id'] = $area; // only one area_id
            $json[$area] = $data;
        }

        unset($data['area_id']); // remove only one area_id
        
        $discount->update($data);
        $discount->areas()->sync($areas);        
 
        $product->discount_details = $json;

        if (isset($data['label_ckecked']) && $data['label_ckecked'] == 1) {
            // make new label
            if ($data['discount_type'] == 'val') {
                $label['slug'] = Discount::DISCOUNT_VAL;
                $label['ar'] = ['name' => $data['discount_value'] . ' ' . __('admin::app.off', [], 'ar')];
                $label['en'] = ['name' => $data['discount_value'] . ' ' . __('admin::app.off', [], 'en')];
            } else {
                $label['slug'] = Discount::DISCOUNT_PER;
                $label['ar'] = ['name' => $data['discount_value'] . '%'];
                $label['en'] = ['name' => $data['discount_value'] . '%'];
            }

            // check if label exists in  label table
            $labelObj = Productlabel::whereTranslation('name', $label)
                            ->whereTranslation('locale', 'en')->first();
            if (!$labelObj) {
                // create new label 
                $label['status'] = 1;
                $labelObj = Productlabel::create($label);
            }

            // assign new label to the product
            $product->productlabel_id = $labelObj->id;
        }
        $product->save();
        return $discount;
    }

    public function setStatus($discount, $data) {

        return $discount->update($data);
    }

    // product id
    public function productHasLabel($data) {
        $product = Product::find($data['product_id']);
        $result = null;
        if ($product->label) {
            $result['product_label'] = $product->label->name;
            $result['product_has_label'] = true;
        }
        return $result;
    }

    /**
     * @param  int  $id
     * @return void
     */
    public function delete($id) {
        parent::delete($id);
    }

}
