<?php

namespace Webkul\Core\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\ActivityLog as ActivityLogContract;

class ActivityLog extends Model implements ActivityLogContract
{
    protected $table = 'activity_log';

    protected $guarded = ['id'];

    protected $casts = [
        'properties' => 'collection',
    ];

    public function handleLogText()
    {
        return __('core::app.logText', [
            'causer'    =>  $this->causer ? $this->causer->name : '',
            'type'     =>  __('core::app.' . $this->action_type),
            'model'     =>  __('core::app.' . $this->log_name),
            'id'     =>  $this->subject_id,
            'time'      =>  $this->created_at
        ]);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function causer()
    {
        return $this->morphTo();
    }
}