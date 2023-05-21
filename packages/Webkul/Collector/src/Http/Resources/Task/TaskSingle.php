<?php

namespace Webkul\Collector\Http\Resources\Task;


use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Webkul\Sales\Models\OrderItem;
use Illuminate\Http\Resources\Json\JsonResource;
class TaskSingle extends JsonResource
{


    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }


    public function toArray($request)
    {

        if(auth()->user()->warehouse->id==$this->from_warehouse_id){
            $taskLabel='تسليم منتج';
            $taskType='Out';
        }
        if(auth()->user()->warehouse->id==$this->to_warehouse_id){
            $taskLabel='استﻻم منتج';
            $taskType='In';
        }

            return [
                'id'         => $this->id,
                'task_type'         => $taskType,
                'task_label'         => $taskLabel,
                'taskItems'     => TaskItemResource::collection($this->transactionProducts),
                'created_at'    => $this->created_at,
                'updated_at'    => $this->updated_at,
            ];


    }

}