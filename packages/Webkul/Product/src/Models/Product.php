<?php

namespace Webkul\Product\Models;

use Webkul\Area\Models\Area;
use Laravel\Scout\Searchable;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Unit;
use Webkul\Banner\Models\Banner;
use Webkul\Area\Models\AreaProxy;
use Webkul\Core\Models\SoldProxy;
use Webkul\Brand\Models\BrandProxy;
use Webkul\Core\Models\ShelveProxy;
use Webkul\Bundle\Models\BundleProxy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Models\ActivityLogProxy;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Bundle\Models\BundleItemProxy;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Supplier\Models\SupplierProxy;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Category\Models\SubCategoryProxy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Inventory\Models\InventoryProductProxy;
use Webkul\Product\Contracts\Product as ProductContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Product extends Model implements ProductContract, TranslatableContract {

    use Translatable,
        Searchable,
        SoftDeletes;

    public $translatedAttributes = [
        'name', 'description'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url', 'thumb_url', 'total_in_stock', 'label_name'];
    protected $fillable = [
        'barcode',
        'prefix',
        'featured',
        'status',
        'minimum_stock',
        'bundle_id',
        'productlabel_id',
        'returnable',
        'price',
        'discount_details',
        'cost',
        'tax',
        'weight',
        'width',
        'height',
        'length',
        'shelve_id',
        'brand_id',
        'unit_id',
        'unit_value',
        'note'
    ];
    protected $casts = [
        'discount_details' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['pivot'];


    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs() {
        return 'products';
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray() {
        return [
            'id' => $this->id,
            'name' => implode(' ', $this->translations()->get()->pluck('name')->toArray()),
            'description' => implode(' ', $this->translations()->get()->pluck('description')->toArray()),
            'barcode' => $this->barcode,
            'brand' => implode(' ', $this->getBrandsInTranslations()),
            'subCategories' => implode(' ', array_unique($this->getSubCategoriesInTranslations()['subCategories'])),
            'categories' => implode(' ', array_unique($this->getSubCategoriesInTranslations()['categories'])),
            'tags' => implode(' ', array_unique($this->getTagsInTranslations())),
        ];
    }

    private function getSubCategoriesInTranslations() {
        $subCategories = [];
        $categories = [];
        foreach ($this->subCategories as $subCategory) {
            $subCategories = array_merge($subCategories, $subCategory->translations()->get()->pluck('name')->toArray());
            foreach ($subCategory->parentCategories as $category) {
                $categories = array_merge($categories, $category->translations()->get()->pluck('name')->toArray());
            }
        }

        return [
            'categories' => $categories,
            'subCategories' => $subCategories,
        ];
    }

    private function getBrandsInTranslations() {
        return $this->brand->translations()->get()->pluck('name')->toArray();
    }

    private function getTagsInTranslations() {
        $tags = [];
        foreach ($this->tags as $tag) {
            $tags = array_merge($tags, $tag->translations()->get()->pluck('name')->toArray());
        }
        return $tags;
    }

    /**
     * Scope a query to only include active products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query) {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include active products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeHasAmount(Builder $query) {
        $areaID = request()->header('area');

        if (!$areaID) {
            $areaID = Area::where('default', '1')->value('id');
        }
        return $query->whereHas('areas', function ($q) use ($areaID) {
                    $q->where('area_id', $areaID)->where('inventory_areas.total_qty', '>', 0);
                });
    }

    /**
     * Get all Logs
     */
    public function logs() {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    /**
     * The sub categories that belong to the product.
     */
    public function subCategories() {
        return $this->belongsToMany(SubCategoryProxy::modelClass(), 'product_sub_categories');
    }

    /**
     * The shelve that has this the product.
     */
    public function shelve() {
        return $this->belongsTo(ShelveProxy::modelClass());
    }

    /**
     * The unit that has this the product.
     */
    public function unit() {
        return $this->belongsTo(Unit::class, 'unit_id');
    }


    /**
     * The Brand that has this the product.
     */
    public function brand() {
        return $this->belongsTo(BrandProxy::modelClass());
    }

    public function bundle() {
        return $this->belongsTo(BundleProxy::modelClass(), 'bundle_id');
    }

    public function bundleItems() {
        return $this->hasMany(BundleItemProxy::modelClass(), 'bundle_id', 'bundle_id');
    }

    /**
     * Get all supplier for the supplier product
     */
    public function suppliers() {
        return $this->belongsToMany(SupplierProxy::modelClass(), 'supplier_products', 'product_id', 'supplier_id');
    }

    public function customers() {
        return $this->belongsToMany(CustomerProxy::modelClass(), 'customer_products', 'product_id', 'customer_id');
    }

    public function tags() {
        return $this->belongsToMany(ProductTagProxy::modelClass(), 'product_tag_related');
    }

    public function label() {
        return $this->belongsTo(ProductlabelProxy::modelClass(), 'productlabel_id');
    }

    public function relatedProducts() {
        $tags = $this->tags()->with(
                        ['products' => function ($q) {
                                $q->active()->hasAmount();
                            }
                        ]
                )->get();

        $products = collect();
        foreach ($tags as $tag) {
            foreach ($tag->products as $product) {
                if ($product->id != $this->id) {
                    $products->push($product);
                }
            }
        }

        return $products->unique('id');
    }

    /**
     * The Inventory in Area that has this the product.
     */
    public function areas() {
        return $this->belongsToMany(AreaProxy::modelClass(), 'inventory_areas', 'product_id', 'area_id')->withPivot('area_id', 'init_total_qty', 'total_qty');
    }

    /**
     * The Warehouse that has this the product.
     */
    public function warehouses() {
        return $this->belongsToMany(WarehouseProxy::modelClass(), 'inventory_warehouses', 'product_id', 'warehouse_id')->withPivot('area_id', 'qty', 'can_order');
    }

    /**
     * Get Total Quantity in One Area
     */
    public function totalQtyInOneArea() {
        return $this->belongsToMany(AreaProxy::modelClass(), 'inventory_areas', 'product_id', 'area_id');
    }

    public function inventoryProducts() {
        return $this->hasMany(InventoryProductProxy::modelClass());
    }

    /**
     * The images that belong to the product.
     */
    public function images() {
        return $this->hasMany(ProductImageProxy::modelClass(), 'product_id');
    }

    public function banner() {
        return $this->belongsTo(Banner::class, 'action_id', 'id');
    }

    public function getImageUrlAttribute() {
        if (!$this->image) {
            return null;
        }
        return Storage::url($this->image);
    }

    public function getThumbUrlAttribute() {
        if (!$this->thumb) {
            return null;
        }
        return Storage::url($this->thumb);
    }

    public function getPriceAttribute($value) {
        if ($value) {
            $valueExploded = explode('.', $value);
            if (isset($valueExploded[1]) && $valueExploded[1] == 0) {
                return $valueExploded[0];
            }
        }
        return $value;
    }

    public function getCostAttribute($value) {
        if ($value) {
            $valueExploded = explode('.', $value);
            if (isset($valueExploded[1]) && $valueExploded[1] == 0) {
                return $valueExploded[0];
            }
        }
        return $value;
    }

    public function getWeightAttribute($value) {
        if ($value) {
            $valueExploded = explode('.', $value);
            if (isset($valueExploded[1]) && $valueExploded[1] == 0) {
                return $valueExploded[0];
            }
        }
        return $value;
    }

    public function getWidthAttribute($value) {
        if ($value) {
            $valueExploded = explode('.', $value);
            if (isset($valueExploded[1]) && $valueExploded[1] == 0) {
                return $valueExploded[0];
            }
        }
        return $value;
    }

    public function getHeightAttribute($value) {
        if ($value) {
            $valueExploded = explode('.', $value);
            if (isset($valueExploded[1]) && $valueExploded[1] == 0) {
                return $valueExploded[0];
            }
        }
        return $value;
    }

    public function getLengthAttribute($value) {
        if ($value) {
            $valueExploded = explode('.', $value);
            if (isset($valueExploded[1]) && $valueExploded[1] == 0) {
                return $valueExploded[0];
            }
        }
        return $value;
    }

    public function getTotalInStockAttribute() {
        $areaID = request()->header('area');
        if (!$areaID) {
            $areaID = Area::where('default', '1')->value('id');
        }
        if ($this->bundle_id) {
            // method one 
            // get the max allwoed qty of bundle
            // depending on the min qty of product id
            $bunldeItems = $this->bundle->items;
            $qtyInStock = [];
            foreach ($bunldeItems as $item) {
                $invAreay = InventoryArea::where(['product_id' => $item->product_id, 'area_id' => $areaID])->first();
                if ($invAreay) {
                    $invQty = $invAreay->total_qty ;
                    $bundleQty = $item->quantity;
                    $qty = $invQty>0 ? $invQty / $bundleQty:0; // 15 / 4 = 3.75 = 3

                    array_push($qtyInStock, intval($qty));
                }else{
                    array_push($qtyInStock, 0);
                }
            }

            return min($qtyInStock) ?? 0;

            // return min($qtyInStock) > $this->bundle->amount ? $this->bundle->amount :  min($qtyInStock)  ?? 0;
            // method two 
            // select minQty of inventory area of select products in bundle
            // $bunldeProductIds = $bunldeItems->pluck('product_id');
            // $minQty = InventoryArea::whereIn('product_id', $bunldeProductIds)->min('total_qty');
            // return $minQty ?? 0;
        } else {
            $quantity = $this->areas()->where('area_id', $areaID)->first()->pivot->total_qty ?? 0;
            $limitedProducts = collect(config('robosto.LIMITED_PRODUCTS_QUANTITY'));
            $product = $limitedProducts->where('product_id', $this->id)->where('from', '<=', now()->format('Y-m-d H:i:s'))->where('to', '>=', now()->format('Y-m-d H:i:s'))->first();
            if ($product && $quantity > $product['max_qty']){
                $quantity = $product['max_qty'];
            }
            return $quantity;
        }
    }

    public function getLabelNameAttribute($value) {
        $label = '';
        $labelObj = $this->label()->first();

        if ($labelObj) {
            $label = $labelObj->name;
        }

        return $label;
    }

    public function solds() {
        return $this->morphMany(SoldProxy::modelClass(), 'soldable');
    }

}
