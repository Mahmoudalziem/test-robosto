<?php
namespace Webkul\Sales\Http\Traits;

use Webkul\Area\Models\Area;
use Webkul\Core\Models\Channel;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\CustomerAddress;

trait PrepareOrderData
{

    /**
     * @param array $data
     * @param string $source
     *
     * @return array
     */
    public function prepareOrderData(array $data, string $source = 'APP')
    {
        Log::info('Order Data');
        Log::info($data);

        $data['customer_id'] = $source == 'PORTAL' ? $data['customer_id'] : auth('customer')->id();
        if ($source == 'PORTAL') {
            $data['call_enter'] = auth('admin')->id();
        }

        $data['channel_id'] = $source == 'PORTAL' ? Channel::CALL_CENTER : Channel::MOBILE_APP;
        $customerAddress = CustomerAddress::find($data['address_id']);
        $data['area_id'] = $customerAddress->area_id;
        $data['customer_address'] = $customerAddress;
        $data['old_items'] = $data['items'];
        // Pass Data by reference to handle shadow area
        $this->getShadowArea($data);

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function getShadowArea(array &$data)
    {
        if (isset($data['area_id'])) {
            $area = Area::find($data['area_id']);
            if ($area && $area->mainArea) {
                $data['shadow_area_id'] = $data['area_id'];
                $data['area_id'] = $area->mainArea->id;
            }
        }
    }
}