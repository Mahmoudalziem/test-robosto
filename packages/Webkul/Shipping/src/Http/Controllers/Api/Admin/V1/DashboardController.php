<?php

namespace Webkul\Shipping\Http\Controllers\Api\Admin\V1;

use Webkul\Area\Models\Area;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Shipping\Models\Shippment;

class DashboardController extends BackendBaseController
{
    public function shippmentsOverview()
    {
        $shippmentsStatusCollection = Shippment::selectRaw("
                                        COUNT(CASE WHEN `status` in ('" . Shippment::STATUS_PENDING . "') THEN 1 END) AS 'pending_shippments',
                                        COUNT(CASE WHEN `status` in ('" . Shippment::STATUS_SCHEDULED . "') THEN 1 END) AS 'scheduled_shippments',
                                        COUNT(CASE WHEN `status` in ('" . Shippment::STATUS_RESCHEDULED . "') THEN 1 END) AS 're_scheduled_shippments',
                                        COUNT(CASE WHEN `current_status` in ('" . Shippment::CURRENT_STATUS_DISPATCHING . "') THEN 1 END) AS 'on_the_way_shippments',
                                        COUNT(CASE WHEN `status` in ('" . Shippment::STATUS_DELIVERED . "') THEN 1 END) AS 'delivered_shippments',
                                        COUNT(CASE WHEN `status` in ('" . Shippment::STATUS_FAILED . "') THEN 1 END) AS 'failed_shippments',
                                        COUNT('id') AS 'total_shippments'
                                        ")->where('shipper_id', auth()->id())->get();
        $shippmentsStatusCollection = $shippmentsStatusCollection[0];
        $countData['shippments'] = [
            'pending_shippments' => $shippmentsStatusCollection->pending_shippments,
            'scheduled_shippments' => $shippmentsStatusCollection->scheduled_shippments,
            're_scheduled_shippments' => $shippmentsStatusCollection->re_scheduled_shippments,
            'on_the_way_shippments' => $shippmentsStatusCollection->on_the_way_shippments,
            'delivered_shippments' => $shippmentsStatusCollection->delivered_shippments,
            'failed_shippments' => $shippmentsStatusCollection->failed_shippments,
            'total_shippments' => $shippmentsStatusCollection->total_shippments
        ];

        return $this->responseSuccess($countData);
    }

    public function areaList() {
        return $this->responseSuccess(Area::active()->get());
    }
}
