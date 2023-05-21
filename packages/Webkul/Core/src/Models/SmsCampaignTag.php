<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\SmsCampaignTag as SmsCampaignTagContract;

class SmsCampaignTag extends Model implements SmsCampaignTagContract
{
    protected $fillable = [];
}