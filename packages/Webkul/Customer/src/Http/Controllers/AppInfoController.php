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

class AppInfoController extends BackendBaseController
{

    /**
     * Load the avatars for the customer.
     *
     * @return JsonResponse
     */
    public function get()
    {
        $settingsApp = Setting::whereIn('group', ['app', 'customer'])->get(['key', 'value']);
        $settingsSocial = Setting::where('group', 'social')->get(['key', 'value', 'icon']);

        $appInfo=[];
        $dataApp = [];
        $dataSocial = [];
        if ($settingsApp) {
            foreach ($settingsApp as $row) {
                $dataApp[$row->key] = $row->value;
            }
            $appInfo = $dataApp;
        }

        if ($settingsSocial) {
            foreach ($settingsSocial as $row) {
                $dataSocial[] = ['url' => $row->value, 'icon' => $row->icon];
            }
            $appInfo['social'] = $dataSocial ;
        }

        // Fire Event
        Event::dispatch('customer.get.app-info', $appInfo);

        return $this->responseSuccess($appInfo);
    }


}
