<?php

namespace Webkul\Admin\Repositories\Inventory;

use Webkul\Core\Eloquent\Repository;


class InventoryAdjustmentProductRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return "Webkul\Inventory\Models\InventoryAdjustmentProduct";
    }
}