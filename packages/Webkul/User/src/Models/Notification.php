<?php

namespace Webkul\User\Models;

use Webkul\Core\Models\TagProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\User\Contracts\Notification as NotificationContract;

class Notification extends Model implements NotificationContract
{
    protected $fillable = ['title', 'body', 'scheduled_at', 'fired','filter'];
    protected $casts = [
        'filter' => 'array',
    ];
    
    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function tags()
    {
        return $this->belongsToMany(TagProxy::modelClass(), 'notification_tags', 'notification_id', 'tag_id');
    }
}