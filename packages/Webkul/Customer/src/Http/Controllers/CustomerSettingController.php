<?php

namespace Webkul\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Core\Models\Setting;
use Webkul\Customer\Http\Resources\Banner\AppInfoAll;
use Webkul\Customer\Models\Customer;


class CustomerSettingController extends BackendBaseController
{

    /**
     * Load the avatars for the customer.
     *
     * @return JsonResponse
     */
    public function get()
    {
        $customer=auth('customer')->user();
        $showData=[];
        $settings  = $customer->settings()->get(['key','value' ]);
        
        foreach($settings as $setting){
            $showData[$setting->key]= is_numeric($setting->value) ? (int) $setting->value  :  $setting->value;
        }
 
        // Fire Event
        Event::dispatch('customer.get.settings', $settings);
        return $this->responseSuccess( $showData);
    }

    public function update( )
    {
        $customer=auth('customer')->user();
        $array = (array) request()->all() ;
        foreach ($array as $key=>$val){
            $setting=$customer->settings()->where('key',$key)->update(['value'=>$val ]);
        }

        // Fire Event
        Event::dispatch('customer.update.settings', $setting);
        return $this->responseSuccess( );
    }
}
