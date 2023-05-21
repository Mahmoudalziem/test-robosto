<?php

namespace Webkul\Shipping\Repositories;

use Webkul\Core\Eloquent\Repository;

class ShipperRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Shipping\Contracts\Shipper';
    }

    public function list($request) {
        $query = $this->newQuery();
        if ($request->has('sort') && !empty($request->sort)) {
            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        if ($request->exists('date_from') && !empty($request['date_from']) && $request->exists('date_to') && !empty($request['date_to'])) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request['date_from'] . ' 00:00:00';
                $dateTo = $request['date_to'] . ' 23:59:59';
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            });
        }

        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->where('name', 'LIKE', '%' . trim($request->filter) . '%');
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