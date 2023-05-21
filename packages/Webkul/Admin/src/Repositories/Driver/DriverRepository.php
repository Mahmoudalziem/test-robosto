<?php

namespace Webkul\Admin\Repositories\Driver;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Webkul\Brand\Contracts\Brand;
use Webkul\Core\Eloquent\Repository;
use Webkul\Driver\Models\DriverLogBreak;
use Webkul\Driver\Models\DriverLogEmergency;
use Webkul\Driver\Models\DriverLogLogin;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderDriverDispatch;

class DriverRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Driver\Contracts\Driver';
    }


    public function list($request){
        $query = $this->newQuery();
        $query= $query->byArea();
        $query->paginate();
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
            $query->where('name', 'LIKE', '%'.  trim($request->filter).'%')
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
     * @return Brand
     */
    public function create(array $data)
    {
        $data['password']= bcrypt($data['password']);
        $data['status']= 1;
        $driver = $this->model->create($data);

        // Store image
        $this->saveImgBase64($data, $driver);
        $this->saveImgBase64($data, $driver,'image_id');
        return $driver;
    }

    /**
     * @param  array  $data
     * @param  int  $id
     * @param  string  $attribute
     * @return Brand
     */
    public function update(array $data, $id, $attribute = "id")
    {
        $driver = $this->findOrFail($id);
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        
        $area_id= \Webkul\Inventory\Models\Warehouse::find($data['warehouse_id'])->area_id;
        $data['area_id']=$area_id;
        
        $driver->update($data);

        // Store image
        if (isset($data['image'])) {
            $this->saveImgBase64($data, $driver);
        }

        if (isset($data['image_id'])) {
            $this->saveImgBase64($data, $driver,'image_id');
        }

        return $driver;
    }

    /**
     * @param  int  $id
     * @return void
     */
    public function delete($id)
    {
        parent::delete($id);
    }
    public function logs($type,$driverId){

        if($type =='login'){
            return DriverLogLogin::where('driver_id',$driverId)->orderBy('id','desc')->paginate();
        }

        if($type =='break'){
            return DriverLogBreak::where('driver_id',$driverId)->orderBy('id','desc')->paginate();
        }
        if($type =='emergency'){
            return DriverLogEmergency::where('driver_id',$driverId)->orderBy('id','desc')->paginate();
        }
    }

    public function orders($driverId,$request){

        $query = $this->newQuery();
        $query=$query->findOrFail($driverId);
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

    public function ordersDriverDispatching($driverId,$request){

        $driver=$this->model->findOrFail($driverId);
        $query=OrderDriverDispatch::where([  'driver_id'=>$driver->id]) ;


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
