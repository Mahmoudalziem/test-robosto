<?php

namespace Webkul\Admin\Http\Resources\Alert;

use App\Http\Resources\CustomResourceCollection;

class AlertAll extends CustomResourceCollection {

    public function toArray($request) {

        return $this->collection->map(function ($alert) {

                    return [
                'id' => $alert->id,
                'admin_type' => $alert->admin_type,
                'key' => $alert->key,
                'model' => $alert->model,
                'model_id' => $alert->model_id,
                'direct_to' => $alert->direct_to,
                'title' => $alert->title,
                'body' => $alert->body,
                'read' => $alert->me->read   ,
                'created_at' => $alert->created_at,
                'updated_at' => $alert->updated_at,
                    ];
                });
    }

}
