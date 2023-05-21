<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\CustomerSmsSetting as CustomerSmsSettingContract;

class CustomerSmsSetting extends Model implements CustomerSmsSettingContract
{
    protected $fillable = ['customer_id','sms_type','sent'];
}