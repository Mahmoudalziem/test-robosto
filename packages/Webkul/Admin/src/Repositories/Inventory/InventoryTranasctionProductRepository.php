<?php

namespace Webkul\Admin\Repositories\Inventory;

use Webkul\Core\Eloquent\Repository;
use Webkul\Inventory\Contracts\InventoryTransactionProduct;

class InventoryTranasctionProductRepository extends Repository
{


    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return InventoryTransactionProduct::class;
    }


}