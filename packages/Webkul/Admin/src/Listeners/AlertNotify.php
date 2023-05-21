<?php

namespace Webkul\Admin\Listeners;

use Webkul\User\Models\Role;
use Webkul\Core\Models\Alert;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Core\Services\SendPushNotification;
use Webkul\Core\Services\SendNotificationUsingFCM;

class AlertNotify implements ShouldQueue 
{

    /**
     * @param string model $senderModel
     * @param array $payload

     * 
     * @return [type]
     */

    public function driver_sign_in(model $senderModel, array $payload = null) {

        $model = $payload['model']; // driver
        $modelArea = $model->area; // driver area        
        $modelStore = $model->warehouse; // driver warehouse

        $admins = $this->adminAreaManger($senderModel, $modelArea);

        foreach (core()->getAllLocales() as $locale) {
            $data[$locale->code] = [
                'title' => __('core::alert.title_driver_sign_in', ['driver' => $model->name, 'store' => $modelStore->translate($locale->code)->name, 'time' => now()]),
                'body' => __('core::alert.body_driver_sign_in', ['driver' => $model->name, 'store' => $modelStore->translate($locale->code)->name, 'time' => now()]),
            ];
        }

        $data['admin_type'] = [Role::AREA_MANAGER];
        $data['key'] = 'driver_sign_in';
        $data['direct_to'] = 'drivers-sign-in-out-logs';
        $data['model'] = get_class($model);
        $data['model_id'] = $model->id;

        // send to operation manager   
        $alert=$this->saveAlert($admins, $data);
        $data['alert_id']=$alert->id;
        $this->sendNotificationToAdmin($admins, $data);
    }

    public function driver_sign_out(model $senderModel, array $payload = null) {

        $model = $payload['model'];
        $modelArea = $model->area; //  area             
        $modelStore = $model->warehouse;
        $admins = $this->adminAreaManger($senderModel, $modelArea);

        foreach (core()->getAllLocales() as $locale) {
            $data[$locale->code] = [
                'title' => __('core::alert.title_driver_sign_out', ['driver' => $model->name, 'store' => $modelStore->translate($locale->code)->name, 'time' => now()]),
                'body' => __('core::alert.body_driver_sign_out', ['driver' => $model->name, 'store' => $modelStore->translate($locale->code)->name, 'time' => now()]),
            ];
        }

        $data['admin_type'] = [Role::AREA_MANAGER];
        $data['key'] = 'driver_sign_out';
        $data['direct_to'] = 'drivers-sign-in-out-logs';
        $data['model'] = get_class($model);
        $data['model_id'] = $model->id;

        // send to operation manager   
        $alert=$this->saveAlert($admins, $data);
        $data['alert_id']=$alert->id;
        $this->sendNotificationToAdmin($admins, $data);
    }

    public function driver_request_break(model $senderModel, array $payload = null) {


        $model = $payload['model'];
        $modelArea = $model->area; //  area         
        $modelStore = $model->warehouse;
        $admins = $this->adminAreaManger($senderModel);

        foreach (core()->getAllLocales() as $locale) {
            $data[$locale->code] = [
                'title' => __('core::alert.title_driver_request_break', ['driver' => $model->name, 'store' => $modelStore->translate($locale->code)->name, 'duration' => $payload['duration']]),
                'body' => __('core::alert.body_driver_request_break', ['driver' => $model->name, 'store' => $modelStore->translate($locale->code)->name, 'duration' => $payload['duration']]),
            ];
        }

        $data['admin_type'] = [Role::AREA_MANAGER];
        $data['key'] = 'driver_request_break';
        $data['direct_to'] = 'drivers-breaks-logs';
        $data['model'] = get_class($model);
        $data['model_id'] = $model->id;

        // send to operation manager   
        $alert=$this->saveAlert($admins, $data);
        $data['alert_id']=$alert->id;
 
        $this->sendNotificationToAdmin($admins, $data);
    }

    public function driver_cancelled_order(model $senderModel, array $payload = null) {


        $model = $payload['model']; // order
        $modelArea = $model->area; //  area         
        $modelStore = $model->warehouse;
        $admins = $this->adminAreaManger($senderModel);

        foreach (core()->getAllLocales() as $locale) {
            $data[$locale->code] = [
                'title' => __('core::alert.title_driver_cancelled_order', ['driver' => $senderModel->name, 'store' => null, 'id' => $model->id]),
                'body' => __('core::alert.body_driver_cancelled_order', ['driver' => $senderModel->name, 'store' => null, 'id' => $model->id]),
            ];
        }

        $data['admin_type'] = [Role::AREA_MANAGER];
        $data['key'] = 'driver_cancelled_order';
        $data['direct_to'] = 'drivers-orders';
        $data['model'] = get_class($model);
        $data['model_id'] = $senderModel->id; // redirct to driver log orders
        // send to operation manager   
        $alert=$this->saveAlert($admins, $data);
        $data['alert_id']=$alert->id;
        $this->sendNotificationToAdmin($admins, $data);
    }

    public function collector_sign_in(model $senderModel, array $payload = null) {

        $model = $payload['model'];
        $modelArea = $model->area; //  area        
        $modelStore = $model->warehouse; //  warehouse
        $admins = $this->adminAreaManger($senderModel, $modelArea);

        foreach (core()->getAllLocales() as $locale) {
            $data[$locale->code] = [
                'title' => __('core::alert.title_collector_sign_in', ['collector' => $model->name, 'time' => now()]),
                'body' => __('core::alert.body_collector_sign_in', ['collector' => $model->name, 'time' => now()]),
            ];
        }

        $data['admin_type'] = [Role::AREA_MANAGER];
        $data['key'] = 'collector_sign_in';
        $data['direct_to'] = 'collectors-logs';
        $data['model'] = get_class($model);
        $data['model_id'] = $model->id;

        // send to operation manager   
        $alert=$this->saveAlert($admins, $data);
        $data['alert_id']=$alert->id;
        $this->sendNotificationToAdmin($admins, $data);
    }

    public function collector_sign_out(model $senderModel, array $payload = null) {

        $model = $payload['model'];
        $modelArea = $model->area; //  area        
        $modelStore = $model->warehouse; //  warehouse
        $admins = $this->adminAreaManger($senderModel, $modelArea);

        foreach (core()->getAllLocales() as $locale) {
            $data[$locale->code] = [
                'title' => __('core::alert.title_collector_sign_out', ['collector' => $model->name, 'store' => $modelStore->translate($locale->code)->name, 'time' => now()]),
                'body' => __('core::alert.body_collector_sign_out', ['collector' => $model->name, 'store' => $modelStore->translate($locale->code)->name, 'time' => now()]),
            ];
        }

        $data['admin_type'] = [Role::AREA_MANAGER];
        $data['key'] = 'collector_sign_out';
        $data['direct_to'] = 'collectors-logs';
        $data['model'] = get_class($model);
        $data['model_id'] = $model->id;

        // send to operation manager   
        $alert=$this->saveAlert($admins, $data);
        $data['alert_id']=$alert->id;
        $this->sendNotificationToAdmin($admins, $data);
    }

    public function admin_create_purchase_order(model $senderModel, array $payload = null) {

        $model = $payload['model'];
        $admins = $this->adminOperationManger();

        foreach (core()->getAllLocales() as $locale) {
            $data[$locale->code] = [
                'title' => __('core::alert.title_new_purchase_order_by_admin', ['id' => $model->id, 'admin' => $senderModel->name]),
                'body' => __('core::alert.body_new_purchase_order_by_admin', ['id' => $model->id, 'admin' => $senderModel->name]),
            ];
        }

        $data['admin_type'] = [Role::OPERATION_MANAGER];
        $data['key'] = 'admin_create_purchase_order';
        $data['direct_to'] = 'purchase-orders-profile';
        $data['model'] = get_class($model);
        $data['model_id'] = $model->id;

        // send to operation manager   
        $alert=$this->saveAlert($admins, $data);
        $data['alert_id']=$alert->id;
        $this->sendNotificationToAdmin($admins, $data);
    }

    public function admin_create_transfer_order(model $senderModel, array $payload = null) {

        $model = $payload['model'];
        $admins = $this->adminOperationManger();

        foreach (core()->getAllLocales() as $locale) {
            $data[$locale->code] = [
                'title' => __('core::alert.title_new_transfer_order_by_admin', ['id' => $model->id, 'admin' => $senderModel->name]),
                'body' => __('core::alert.body_new_transfer_order_by_admin', ['id' => $model->id, 'admin' => $senderModel->name]),
            ];
        }

        $data['admin_type'] = [Role::OPERATION_MANAGER];
        $data['key'] = 'admin_create_transfer_order';
        $data['direct_to'] = 'transfers-profile';
        $data['model'] = get_class($model);
        $data['model_id'] = $model->id;

        // send to operation manager   
        $alert=$this->saveAlert($admins, $data);
        $data['alert_id']=$alert->id;
        $this->sendNotificationToAdmin($admins, $data);
    }

    public function admin_create_adjustment_order(model $senderModel, array $payload = null) {

        $model = $payload['model'];
        $admins = $this->adminOperationManger();
 

        foreach (core()->getAllLocales() as $locale) {
            $data[$locale->code] = [
                'title' => __('core::alert.title_new_adjustment_order_by_admin', ['id' => $model->id, 'admin' => $senderModel->name]),
                'body' => __('core::alert.title_new_adjustment_order_by_admin', ['id' => $model->id, 'admin' => $senderModel->name]),
            ];
        }
        $data['admin_type'] = [Role::OPERATION_MANAGER];
        $data['key'] = 'admin_create_adjustment_order';
        $data['direct_to'] = 'adjustments-profile';
        $data['model'] = get_class($model);
        $data['model_id'] = $model->id;

        // send to operation manager   
        $alert=$this->saveAlert($admins, $data);
        $data['alert_id']=$alert->id;
        $this->sendNotificationToAdmin($admins, $data);
    }

    public function admin_cancelled_order(model $senderModel , array $payload = null) {

        $model = $payload['model'];

        $adminOperationMangers = $this->adminOperationManger();
        $adminAreaMangers = $this->adminAreaManger($senderModel, $model->area);
        $admins = $adminOperationMangers->merge($adminAreaMangers);

        foreach (core()->getAllLocales() as $locale) {
            $data[$locale->code] = [
                'title' => __('core::alert.title_order_cancelled_by_admin', ['id' => $model->id, 'admin' => $senderModel->name]),
                'body' => __('core::alert.body_order_cancelled_by_admin', ['id' => $model->id, 'admin' => $senderModel->name]),
            ];
        }

        $data['admin_type'] = [Role::OPERATION_MANAGER, Role::AREA_MANAGER];
        $data['key'] = 'admin_cancelled_order';
        $data['direct_to'] = 'order-profile';
        $data['model'] = get_class($model);
        $data['model_id'] = $model->id;

        // send to operation manager   
        $alert = $this->saveAlert($admins, $data);
        $data['alert_id'] = $alert->id;
        $this->sendNotificationToAdmin($admins, $data);
    }

    // get admin that has role 'operation-manager'
    protected function adminOperationManger() {
        // get role id
        $role = Role::where('slug', Role::OPERATION_MANAGER)->first();

        // get admin who are operation manager
        $adminOperationManger = Admin::whereHas('areas', function ($q) {
                    $q->where('area_id', '!=', null);
                })->whereHas('roles', function ($q) use ($role) {
            $q->where('role_id', $role->id);
        });
        return $adminOperationManger->get();
    }

    // get admin that has role 'operation-manager'
    protected function adminAreaManger(Model $sender, $area = null) {

        // if we send to area manager depends on order 
        if ($area) {
            $senderArea = $area->id;
        } else {
            $senderArea = $sender->areas ? $sender->areas()->first()->id : $sender->area->id;
        }


        //   Log::info(['sender :'=> $sender  ,'senderArea :'=> $sender->area_id ,' senderStore :'=> $sender->warehouse]);
        // get admin that has role 'area-manager'
        // get role id
        $role = Role::where('slug', Role::AREA_MANAGER)->first();

        // get admin who are area manager
        $adminAreaManger = Admin::whereHas('areas', function ($q) use ($senderArea) {
                    $q->where('area_id', $senderArea);
                })->whereHas('roles', function ($q) use ($role) {
            $q->where('role_id', $role->id);
        });
        //  Log::info(['adminAreaManger :'=> $adminAreaManger->get()]);
        return $adminAreaManger->get();
    }

    protected function saveAlert($admins, $data) {
        $admins = $admins->pluck('id');

        $alert = Alert::create($data);

        $alert->admins()->sync($admins->toArray());
       // Log::info($alert);

        return $alert;
    }

    /**
     * Handle the event.
     *
     * @return bool
     * @throws InvalidOptionsException
     */
    private function sendNotificationToAdmin($admins, $data) {
 
        $tokens = [];
        foreach ($admins as $admin) {
            $tokens = array_merge($tokens, $admin->deviceToken->pluck('token')->toArray());
        }

        $data = [
            'data' => ['id'=> $data['alert_id'] ,'key' => $data['key'], 'direct_to' => $data['direct_to'], 'model_id' => $data['model_id'], 'admin_type' => $data['admin_type']],
            'title' => $data['en']['title'],
            'body' => $data['en']['body'],
        ];

        return (new SendNotificationUsingFCM())->sendNotification($tokens, $data);
    }

}
