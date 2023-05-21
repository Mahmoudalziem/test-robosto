<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\SmsCampaign as SmsCampaignContract;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\User\Models\AdminProxy;

class SmsCampaign extends Model implements SmsCampaignContract {

    protected $fillable = ['admin_id','content', 'scheduled_at', 'is_pushed', 'filter'];
    protected $casts = [
        'filter' => 'array',
    ];

    public function tags() {
        return $this->belongsToMany(TagProxy::modelClass(), 'sms_campaign_tags')->withTimestamps();
    }
    
    public function customers() {
        return $this->belongsToMany(CustomerProxy::modelClass(), 'sms_campaign_customers')->withTimestamps();
    } 
    

    public function createdBy() {
        return $this->belongsTo(AdminProxy::modelClass());
    }     

}
