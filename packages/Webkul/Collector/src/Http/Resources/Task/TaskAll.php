<?php

namespace Webkul\Collector\Http\Resources\Task;

use App\Http\Resources\CustomResourceCollection;

class TaskAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {

        return  $this->collection->map(function ($task) {

            if(auth()->user()->warehouse->id==$task->from_warehouse_id){
                $taskLabel='تسليم منتج';
                $taskType='Out';
            }
            if(auth()->user()->warehouse->id==$task->to_warehouse_id){
                $taskLabel='استﻻم منتج';
                $taskType='In';
            }
            return [
                'id'            => $task->id,
                'task_type'         => $taskType,
                'task_label'         => $taskLabel,
                'first_item'         => 'عدد العبوات '.  (int)  number_format($task->transactionProducts()->first()->qty, 0) .' من '. $task->transactionProducts()->first()->product->name,
                'first_item_sku'         => "SKU : ".$task->transactionProducts()->first()->sku,
                'first_item_image' => $task->transactionProducts()->first()->product->image_url,
                'status'         => $task->status,
                'status_name'         => $task->status_name,
                'collector_id'         => auth()->user()->warehouse->id,
                'created_at'    => $task->created_at  ,
            ];
          //  0 => Cancelled, 1 => Pending, 2 => on-the-way, 3 => transferred
        });
    }

}