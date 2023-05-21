<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\AlertAdmin as AlertAdminContract;

class AlertAdmin extends Model implements AlertAdminContract
{
 protected $fillable = ['alert_id','admin_id'  ,  'read'];
}