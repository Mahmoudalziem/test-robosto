<?php

namespace Webkul\Admin\Repositories\Accounting;

use Webkul\User\Models\Role;
use Webkul\User\Models\Admin;
use Webkul\Core\Eloquent\Repository;
use Webkul\Driver\Models\DriverTransactionRequest;
use Webkul\User\Models\AreaManagerTransactionRequest;

class AccountingRepository extends Repository
{

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\User\Contracts\Admin';
    }

    /**
     * @param mixed $request
     * 
     * @return [type]
     */
    public function driverTransactions($request)
    {
        $query = DriverTransactionRequest::latest();

        // if this admin has Area-Manager Role, then fetch just his area transactions
        $admin = auth('admin')->user();
        if ($admin->hasRole([Role::AREA_MANAGER])) {
            $query->whereIn('area_id', auth('admin')->user()->areas->pluck('id')->toArray());
        }

        // Search by Status
        if ($request->exists('status') && !empty($request['status'])) {
            $query->where('status', $request['status']);
        }

        // Search by Area
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->where('area_id', $request['area_id']);
        }

        // Search by Admin
        if ($request->exists('admin_id') && !empty($request['admin_id'])) {
            $query->where('admin_id', $request['admin_id']);
        }
        
        
        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('drivers.name', 'LIKE', '%' . $request->filter . '%');
            });
        }


        if ($request->exists('date_from') && !empty($request['date_from']) && $request->exists('date_to') && !empty($request['date_to'])) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request['date_from'] . ' 00:00:00';
                $dateTo = $request['date_to'] . ' 23:59:59';
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            });
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }


    /**
     * @param mixed $request
     * 
     * @return [type]
     */
    public function areaManagerTransactions($request)
    {
        $query = AreaManagerTransactionRequest::latest();

        // if this admin has Area-Manager Role, then fetch just his areas transactions
        $admin = auth('admin')->user();
        if (!$admin->hasRole([Role::SUPER_ADMIN])) {
            $query->whereIn('area_id', auth('admin')->user()->areas->pluck('id')->toArray());
        }

        // Search by Status
        if ($request->exists('status') && !empty($request['status'])) {
            $query->where('status', $request['status']);
        }

        // Search by Area
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->where('area_id', $request['area_id']);
        }

        // Search by Area Manager
        if ($request->exists('area_manager_id') && !empty($request['area_manager_id'])) {
            $query->where('area_manager_id', $request['area_manager_id']);
        }

        // Search by Acountant
        if ($request->exists('accountant_id') && !empty($request['accountant_id'])) {
            $query->where('accountant_id', $request['accountant_id']);
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }


    /**
     * @param mixed $request
     * 
     * @return [type]
     */
    public function accountantTransactions($request)
    {
        $query = AreaManagerTransactionRequest::latest();

        // if this admin has Area-Manager Role, then fetch just his areas transactions
        $admin = auth('admin')->user();
        if (!$admin->hasRole([Role::SUPER_ADMIN])) {
            $query->whereIn('area_id', auth('admin')->user()->areas->pluck('id')->toArray());
        }

        // Search by Status
        if ($request->exists('status') && !empty($request['status'])) {
            $query->where('status', $request['status']);
        }

        // Search by Area
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->where('area_id', $request['area_id']);
        }

        // Search by Area Manager
        if ($request->exists('area_manager_id') && !empty($request['area_manager_id'])) {
            $query->where('area_manager_id', $request['area_manager_id']);
        }

        // Search by Acountant
        if ($request->exists('accountant_id') && !empty($request['accountant_id'])) {
            $query->where('accountant_id', $request['accountant_id']);
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }


    /**
     * @param mixed $request
     * 
     * @return mixed
     */
    public function areaManagerTransactionRequest(Admin $areaManager, array $data)
    {
        $transaction = $areaManager->areaManagerTransactions()->create($data);

        // Store image
        $this->saveImgBase64WithoutWebP($data, $transaction, 'image', false);

        return $transaction;
    }
}
