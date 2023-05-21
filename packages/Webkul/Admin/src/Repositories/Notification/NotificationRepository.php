<?php

namespace Webkul\Admin\Repositories\Notification;

use Webkul\Core\Eloquent\Repository;

class NotificationRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return 'Webkul\User\Contracts\Notification';
    }

    /**
     * @param $request
     * @return \Webkul\Core\Contracts\Notification
     */
    public function list($request) {

        $query = $this->newQuery();

        // handle sort option
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

        // Search by Name
        if ($request->exists('text') && !empty($request['text'])) {
            $query->where('title', 'LIKE', '%' . $request['text'] . '%')
                    ->orWhere('body', 'LIKE', '%' . $request['text'] . '%');
        }

        // Search by Date
        // if ($request->exists('date') && !empty($request['date'])) {
        //     $query->whereDate('scheduled_at', $request['date']);
        // }
        
        // Search by is published_now
        if ($request->exists('status') && ( ($request['status'] != null) || ($request['status'] != '') )) {
             if($request['status']  == 1){
                 $query->where('scheduled_at', null);
             }else{
                 $query->where('scheduled_at','!=', null);
             }
            
        }        

        if (isset($request['from_date']) && isset($request['to_date']) && !empty($request['from_date']) && !empty($request['to_date'])) {
            $query->whereBetween('created_at', [$request['from_date'] . ' 00:00:00', $request['to_date'] . ' 23:59:59']);
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
     * @return \Webkul\Core\Contracts\Notification
     */
    public function create(array $data) {
       
        $data['fired'] = 0;
        if (!isset($data['scheduled_at']) || empty($data['scheduled_at'])) {
            $data['scheduled_at'] = null;
            $data['fired'] = 1;
        }

        $notification = $this->model->create($data);

        if (isset($data['tags'])) {
            $notification->tags()->sync($data['tags']);
        }
      

        return $notification;
    }

 
    /**
     * @param  array  $data
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Core\Contracts\Notification
     */
    public function update(array $data, $notification) {
        if (!isset($data['scheduled_at']) || empty($data['scheduled_at'])) {
            $data['scheduled_at'] = null;
        }

        $notification->update($data);

        if (isset($data['tags'])) {
            $notification->tags()->sync($data['tags']);
        }

        return $notification;
    }
 

    /**
     * @param  int  $id
     * @return void
     */
    public function delete($id) {
        parent::delete($id);
    }

}
