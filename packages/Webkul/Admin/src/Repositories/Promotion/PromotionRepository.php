<?php

namespace Webkul\Admin\Repositories\Promotion;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Rules\ImageBase64;
use Webkul\Product\Models\Product;
use Webkul\Category\Models\Category;
use Webkul\Core\Eloquent\Repository;
use Webkul\Category\Models\SubCategory;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Promotion\Contracts\Promotion;
use Webkul\Promotion\Models\Promotion as PromotionModel;

class PromotionRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model() {

        return Promotion::class;
    }

    public function list($request) {

        $query = $this->newQuery()->latest();

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
        
        // Search by Status
        if ($request->exists('status') && ( ($request['status'] != null) || ($request['status'] != '') )) {
            $query->where('status', $request['status']);
        }         
        
        // Search by Area
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->whereHas('areas', function ($query) use ($request) {
                $query->where('areas.id',  $request['area_id']);
            });
        }
        

        if ($request->exists('tag') && !empty($request['tag'])) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->whereIn('tag_id', $request['tag']);
            });
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

        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->where(function ($query) use ($request) {
                $query->where('promo_code', 'like', '%' . $request['filter'] . '%');
                $query->orWhereTranslationLike('title', '%' . $request['filter'] . '%');
                $query->orWhere(function ($q) use ($request) {
                    $q->whereHas('tags', function (Builder $q) use ($request) {
                        $tags = DB::table('tags')->select('id')
                                        ->where('name', 'like', '%' . $request['filter'] . '%')
                                        ->pluck('id')->toArray();

                        $q->whereIn('tag_id', $tags);
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

    public function create(array $data) {

        // create promotion
        $promotion = $this->model->create($data);
        // promotion areas
        $promotion->areas()->sync($data['areas']);
        // promotion tags
        $promotion->tags()->sync($data['tags']);

        $apply_content = !is_array($data['apply_content']) ? $data['apply_content'] : null;
        // promotion apply
        $promotion->apply()->create(['apply_type' => $data['apply_type'], 'model_type' => $apply_content]);

        if (is_array($data['apply_content'])) { // array of categories || subcategories || products
            $this->promotionApplyType($promotion, $data);
        }
        return $promotion;
    }

    public function promotionApplyType($promotion, $data) {

        $apply_type = $promotion->apply->apply_type;
        if ($apply_type == "category") {

            foreach ($data['apply_content'] as $contentType) {

                $promotion->apply->categories()->create(['promotion_id' => $promotion->id, 'category_id' => $contentType]);
            }
        }
        if ($apply_type == "subCategory") {
            foreach ($data['apply_content'] as $contentType) {
                $promotion->apply->subcategories()->create(['promotion_id' => $promotion->id, 'sub_category_id' => $contentType]);
            }
        }
        if ($apply_type == "product") {
            foreach ($data['apply_content'] as $contentType) {
                $promotion->apply->{$apply_type . "s"}()->create(['promotion_id' => $promotion->id, 'product_id' => $contentType]);
            }
        }
    }

    public function update(array $data, $promotion) {

        // promotion areas(update)
        $promotion->areas()->sync($data['areas']);
        // promotion tags(update)
        $promotion->tags()->sync($data['tags']);

        
        // if start null then end date is null
        if (!isset($data['start_validity']) || $data['start_validity'] == null || $data['start_validity'] == "") {
            $data['start_validity'] = null;
            $data['end_validity'] = null;
        }
        
        // update new data
        $promotion->update($data);
        
        /**
         * Disable Updating on Applies Content
         */

        // $this->promotionApplyTypeDelete($promotion);        
        // $apply_content = !is_array($data['apply_content']) ? $data['apply_content'] : null;
        // $promotion->apply()->create(['apply_type' => $data['apply_type'], 'model_type' => $apply_content]);
        // if (is_array($data['apply_content'])) { // array of categories || subcategories || products
        //     $this->promotionApplyType($promotion, $data);
        // }


        return $promotion;
    }

    public function promotionApplyTypeDelete($promotion) {
        if ($promotion->apply) {
            $apply_type = $promotion->apply->apply_type;
            if ($apply_type == "category") {
                $promotion->apply->categories()->delete();
            }
            if ($apply_type == "subCategory") {
                $promotion->apply->subcategories()->delete();
            }
            if ($apply_type == "product") {
                $promotion->apply->products()->delete();
            }
            $promotion->apply()->delete();
        }
    }

    /**
     * @param PromotionModel $promotion
     * @param array $exceptions
     * 
     * @return true
     */
    public function savePromotionExceptions(PromotionModel $promotion)
    {
        $query = Product::latest();
        $exceptions = $promotion->exceptions_items;
        

        if ($promotion->apply_type ==  PromotionModel::APPLY_TYPE_CATEGORY) {
            // Search by Category
            $query->whereHas('subCategories', function (Builder $query) use ($exceptions) {
                $query->whereHas('parentCategories', function (Builder $query) use ($exceptions) {
                    $query->whereIn('category_id', $exceptions);
                });
            });
            
        } elseif ($promotion->apply_type == PromotionModel::APPLY_TYPE_SUBCATEGORY) {
            // Search by SubCategory
            $query->whereHas('subCategories', function (Builder $query) use ($exceptions) {
                $query->whereIn('sub_category_id', $exceptions);
            });

        } elseif ($promotion->apply_type == PromotionModel::APPLY_TYPE_PORDUCT) {
            $query->whereIn('id', $exceptions);

        } else {
            $query->whereIn('id', $exceptions);
        }

        // Save Promotion Exception
        $products = [];
        foreach ($query->get()->pluck('id')->toArray() as $product) {
            
            $products[] = [
                'product_id'    =>  $product
            ];
        }

        // Save in DB
        $promotion->exceptionProducts()->createMany($products);
        
        return true;
    }

    public function setStatus($promotion, $data) {
        return $promotion->update($data);
    }

}
