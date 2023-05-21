<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\SmsCampaignCustomer as SmsCampaignCustomerContract;

class SmsCampaignCustomer extends Model implements SmsCampaignCustomerContract
{
    protected $fillable = [];
}