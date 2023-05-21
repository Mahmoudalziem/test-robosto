<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Container\Container as App;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Sales\Contracts\OrderItem;

class OrderItemRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    function model()
    {
        return OrderItem::class;
    }

    /**
     * @param  array  $data
     * @return OrderItem
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }
}