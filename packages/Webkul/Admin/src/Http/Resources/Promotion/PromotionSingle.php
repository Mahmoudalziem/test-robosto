<?php

namespace Webkul\Admin\Http\Resources\Promotion;


use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\SubCategory;
use Webkul\Product\Models\Product;

class PromotionSingle extends JsonResource
{
    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }
    public function toArray($request)
    {
        $apply_type=$this->apply_type;
        $contentType=!$apply_type ?null:$apply_type;
        $exceptionsItems=!$this->exceptions_items?null:$this->exceptions_items;

        if($apply_type == "category"){
            $categories=$this->apply->categories->pluck('category_id')->toArray();
            $contentType=$this->apply->categories? Category::whereIn('id',$categories)->get():null;
            // excetions
            $exceptionsItems=is_array($this->exceptions_items)?Category::whereIn('id',$this->exceptions_items)->get():null;
        }
        if($apply_type == "subCategory"){
            $subCategories= $this->apply->subcategories->pluck('sub_category_id')->toArray();
            $contentType=$this->apply->subcategories? SubCategory::whereIn('id',$subCategories)->get():null;
            // excetions
            $exceptionsItems=is_array($this->exceptions_items)?SubCategory::whereIn('id',$this->exceptions_items)->get():null;
            }
        if($apply_type == "product"){
            $products=$this->apply->products->pluck('product_id')->toArray();
            $contentType=$this->apply->products? Product::whereIn('id',$products)->get():null;
            // excetions
            $exceptionsItems=is_array($this->exceptions_items)? Product::whereIn('id',$this->exceptions_items)->get()  :null;
        }


            return [
                'id'            => $this->id,
                'areas'         => $this->areas->pluck('id')->toArray(),
                'tags'         => $this->tags->pluck('id')->toArray(),
                'ar'         => ['title'=>$this->translate('ar')->title,'description'=>$this->translate('ar')->description]   ,
                'en'         => ['title'=>$this->translate('en')->title,'description'=>$this->translate('en')->description]   ,
                'promo_code'         => $this->promo_code,
                'discount_type'         => $this->discount_type,
                'discount_value'         => $this->discount_value,
                'start_validity'         => $this->start_validity,
                'end_validity'         => $this->end_validity,
                'promo_validity'         => $this->start_validity . ' to '.$this->end_validity,
                'total_vouchers'         => $this->total_vouchers,
                'usage_vouchers'         => $this->usage_vouchers,
                'minimum_order_amount'         => $this->minimum_order_amount,
                'minimum_items_quantity'         => $this->minimum_items_quantity,
                'total_redeems_allowed'         => $this->total_redeems_allowed,
                'price_applied'         => $this->price_applied,
                'apply_type'         => $this->apply_type,
                'apply_content'         => $contentType,
                'exceptions_items'         => $exceptionsItems,
                'send_notifications'         => $this->send_notifications,
                'is_valid'         => $this->is_valid,
                'show_in_app'         => $this->show_in_app,
                'sms_content'         => $this->sms_content,
                'status'         => $this->status,
                'created_at'    => $this->created_at  ,
            ];

    }

}