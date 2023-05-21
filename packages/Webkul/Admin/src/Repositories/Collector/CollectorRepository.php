<?php

namespace Webkul\Admin\Repositories\Collector;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Webkul\Collector\Models\CollectorLogLogin;
use Webkul\Core\Eloquent\Repository;

class CollectorRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Collector\Contracts\Collector';
    }


    public function list($request){
        $query = $this->newQuery();
        $query= $query->byArea();
        // handle sort option
        if ($request->has('sort') && !empty($request->sort) ) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }
 
        // Search by Area
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->where('area_id', $request['area_id']);
        }  
        
        // Search by Warehouse
        if ($request->exists('warehouse_id') && !empty($request['warehouse_id'])) {
            $query->where('warehouse_id', $request['warehouse_id']);
        }         
     
        // Search by Status
        if ($request->exists('status') && ($request['status'] != null)) {
            $query->where('status', $request['status']);
        }
        
        // Search by Online|Offline
        if ($request->exists('is_online') && ($request['is_online'] != null)) {
            $query->where('is_online', $request['is_online']);
        }

        // if filter
        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->where('name', 'LIKE', '%'.  trim($request->filter) .'%')
                    ->orWhere('phone_work', 'LIKE', '%'.  trim($request->filter) .'%');
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
     * @param  array  $data
     * @return \Webkul\Brand\Contracts\Brand
     */
    public function create(array $data)
    {
        $data['password']= bcrypt($data['password']);
        $data['status']= 1;
        
        $collector = $this->model->create($data);

        // Store image
        $this->saveImgBase64($data, $collector);
        $this->saveImgBase64($data, $collector,'image_id');
        
        return $collector;
    }

    /**
     * @param  array  $data
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Brand\Contracts\Brand
     */
    public function update(array $data, $id, $attribute = "id")
    {
        $collector = $this->findOrFail($id);
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        
        $collector->update($data);

        // Store image
        if (isset($data['image'])) {
            $this->saveImgBase64($data, $collector);
        }

        if (isset($data['image_id'])) {
            $this->saveImgBase64($data, $collector,'image_id');
        }

        return $collector;
    }

    /**
     * @param  int  $id
     * @return void
     */
    public function delete($id)
    {
        parent::delete($id);
    }

    public function logs($collectorId){
            return CollectorLogLogin::where('collector_id',$collectorId)->paginate();
    }

    public function orders1($collectorId){
        $collector=$this->model->findOrFail($collectorId);
        return $collector->orders->paginate();
    }

    public function orders($collectorId,$request){

        $query = $this->newQuery();
        $query=$query->findOrFail($collectorId);
        $query=$query->orders();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort) ) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
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
