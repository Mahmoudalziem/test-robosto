<?php
namespace Webkul\Promotion\Services\ApplyPromotion;

use Webkul\Promotion\Models\Promotion;
use Webkul\Promotion\Services\ApplyPromotion\ApplyTypes\ApplyOnCategory;
use Webkul\Promotion\Services\ApplyPromotion\ApplyTypes\ApplyOnProduct;
use Webkul\Promotion\Services\ApplyPromotion\ApplyTypes\ApplyOnSubCategory;
use Webkul\Promotion\Services\ApplyPromotion\ApplyTypes\Type;

class Apply
{

    /**
     * @var Promotion
     */
    public $promotion;

    /**
     * @var string
     */
    private $type;
    
    /**
     * @var array
     */
    private $items;

    /**
     * @var Type
     */
    private $appliable;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(Promotion $promotion, string $type, array $items)
    {
        $this->promotion = $promotion;
        $this->type = $type;
        $this->items = $items;
    }

    /**
     * Get Instance from Apply Type
     */
    public function getTypeInstance()
    {
        if ($this->type == Promotion::APPLY_TYPE_CATEGORY) {
            $this->appliable = new ApplyOnCategory;

        } elseif ($this->type == Promotion::APPLY_TYPE_SUBCATEGORY) {
            $this->appliable = new ApplyOnSubCategory;

        } elseif ($this->type == Promotion::APPLY_TYPE_PORDUCT) {
            $this->appliable = new ApplyOnProduct;

        } else {
            $this->appliable = new ApplyOnCategory;
        }
    }

    /**
     * Apply Promotion on Items and Retun Items
     * 
     * @return array
     */
    public function apply(): array
    {
        // Get Type Instance
        $this->getTypeInstance();

        return $this->appliable->apply($this->promotion, $this->items);
    }
}