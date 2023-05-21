<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Core\Contracts\Notifier as NotifierContract;

class Notifier extends Model implements NotifierContract
{
    public const SMS_TYPE = 'sms';
    public const NOTIFICATION_TYPE = 'notification';
    public const RETENTION_TYPE = 'retention';

    protected $table = 'notifiers';

    protected $fillable = ['entity_type', 'entity_id', 'customer_id'];

    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass());
    }
}
