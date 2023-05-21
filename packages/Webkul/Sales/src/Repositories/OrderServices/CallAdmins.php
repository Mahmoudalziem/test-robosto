<?php

namespace Webkul\Sales\Repositories\OrderServices;

use App\Jobs\MakePhoneCall;
use Webkul\User\Models\Role;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CallAdmins
{

    /**
     * @param Order $order
     * 
     * @return mixed
     */
    public function callAreaAndOperationManagers(Order $order)
    {
        // first of all, check that the order still in pending status
        if ($order->status != Order::STATUS_PENDING) {
            return false;
        }

        $areaManagers = $this->getAdminsWithRoleInArea([Role::AREA_MANAGER], [$order->area_id]);
        $operationsManagers = $this->getAdminsWithRoleInArea([Role::OPERATION_MANAGER], [$order->area_id]);

        // Get Area Managers
        if ($areaManagers) {
            // Get Area Managers Phones
            $areaPhones = $areaManagers->pluck('phone_work')->toArray();
            $areaManagerDuration = config('robosto.CALL_AREA_MANAGER_DURATION');
            // Fire the Job
            MakePhoneCall::dispatch($order, $areaPhones, $areaManagerDuration)->delay(now()->addSeconds($areaManagerDuration));
        }
        
        // Get Operation Managers
        if ($operationsManagers) {
            // Get Operation Managers Phones
            $operationPhones = $operationsManagers->pluck('phone_work')->toArray();
            $operationManagerDuration = config('robosto.CALL_OPERATION_MANAGER_DURATION');
            // Fire the Job
            MakePhoneCall::dispatch($order, $operationPhones, $operationManagerDuration)->delay(now()->addSeconds($operationManagerDuration));
        }

        return true;        
    }

    /**
     * @param array|null $phones
     * @param int|null $delays
     * 
     * @return mixed
     */
    public function callPhones(Order $order, array $phones = null, int $delays = null)
    {
        // first of all, check that the order still in pending status
        if ($order->status != Order::STATUS_PENDING) {
            return false;
        }

        foreach ($phones as $phone) {
            $this->makePhoneCall($phone);
        }

        MakePhoneCall::dispatch($order, $phones, $delays)->delay(now()->addSeconds($delays));
    }

    /**
     * @param array $role
     * @param array $area
     * 
     * @return Collection|null
     */
    public function getAdminsWithRoleInArea(array $role, array $area)
    {
        return DB::table('admins')
        ->join('admin_roles', 'admins.id', '=', 'admin_roles.admin_id')
        ->join('roles', function ($join) use ($role) {
            $join->on('admin_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.slug', $role);
        })
            ->join('admin_areas', 'admins.id', '=', 'admin_areas.admin_id')
            ->join('areas', function ($join) use ($area) {
                $join->on('admin_areas.area_id', '=', 'areas.id')
                ->whereIn('areas.id', $area);
            })
            ->whereNotNull('admins.phone_work')
            ->select('admins.id', 'admins.name', 'admins.email', 'admins.phone_work')
            ->get();
    }


    /**
     * @param string $phone
     * @return void
     * @throws InvalidOptionsException
     */
    public function makePhoneCall($phone)
    {
        Log::info("Call Admin -> " . $phone);
        $url = 'https://api-gateway.innocalls.com/api/call-campaign/v1/call';
        $headers = [
            'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpc3MiOiJodHRwczpcL1wvZ2xvY29tc3lzdGVtcy5jb20iLCJhdWQiOiJodHRwczpcL1wvZ2xvY29tc3lzdGVtcy5jb20iLCJpYXQiOjEzNTY5OTk1MjQsIm5iZiI6MTM1Njk5OTUyNCwiaWQiOiI0YjhmNGNmYi1jMTgwLTQ5NGYtODVmOS1iNjYwY2M2YzA0OWQiLCJnY2kiOiJhYjQ3YWYwYi1mMzcyLTQxOTItYjc4OC04OWVhYTJjYzQ0ZTMifQ.U4t2CkzUFTqNZ1v0bZ9I-AVq88QSvUGz5J_MtdJeFfTFNITCBJYsuvbdjNHZP3pEpwG-DwX7qwzbfGOCmp77Zw',
            'Cookie: __cfduid=daad03eb7971ddd1911b7555cbe085dc81617009241; DO-LB=node-187928191|YGmNG|YGmMH',
            'Content-Type: application/json'
        ];
        $data = [
            'phone' => '2' . $phone,
            'call_flow_id' => '5ee8caa982147900208dedd4',
            'sound_id'  =>  76
        ];

        $response = requestWithCurl($url, 'POST', $data, $headers);
    }

}