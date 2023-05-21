<?php

namespace Webkul\Admin\Repositories\ActivityLog;

use Webkul\Core\Eloquent\Repository;

class ActivityLogRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Core\Contracts\ActivityLog';
    }

    /**
     * @param $request
     * @return \Webkul\Core\Contracts\ActivityLog
     */
    public function list($request)
    {
        $query = $this->newQuery();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multi-sort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'asc');
        }

        // Search by Date
        if (isset($request['from_date']) && !empty($request['from_date'])) {
            $query->whereDate('created_at', $request['from_date']);
        }
        
        // Search by Admin
        if ($request->exists('causer_id') && !empty($request['causer_id'])) {
            $query->where('causer_id', $request['causer_id']);
        }
        
        
        // Search by Log Name
        if ($request->exists('log_name') && !empty($request['log_name'])) {
            $query->where('log_name', $request['log_name']);
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

}
