<?php

namespace Webkul\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Driver\Contracts\SupervisorRating as SupervisorRatingContract;
use Webkul\User\Models\AdminProxy;

class SupervisorRating extends Model implements SupervisorRatingContract
{
    protected $table = 'supervisor_ratings';

    protected $fillable = [ 'rate', 'admin_id', 'driver_id' ];

    public function driver()
    {
        return $this->belongsTo(DriverProxy::modelClass());
    }
    
    public function admin()
    {
        return $this->belongsTo(AdminProxy::modelClass());
    }
}