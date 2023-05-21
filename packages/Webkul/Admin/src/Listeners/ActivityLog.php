<?php

namespace Webkul\Admin\Listeners;

use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;

class ActivityLog implements ShouldQueue
{
    
    /**
     * @param string $actionType
     * @param string $logName
     * @param Model $subject
     * @param Admin $causer
     * @param mixed $data
     * @param null $before
     * @param string|null $description
     * 
     * @return [type]
     */
    public function activityLog(string $actionType, string $logName, Model $subject, Admin $causer, $data, $before = null, string $description = null)
    {
        // Get Model Data
        $properties['data'] = $data;

        // Get Data before updated
        if (!is_null($before)) {
            $properties['before'] = $before;
        }

        // Save activity log for this Model
        $subject->logs()->create([
            'action_type'   =>  $actionType,
            'log_name'      =>  $logName,
            'causer_type'   =>  get_class($causer),
            'causer_id'     =>  $causer->id,
            'properties'    =>  $properties,
            'description'   =>  $description,
        ]);
    }
}