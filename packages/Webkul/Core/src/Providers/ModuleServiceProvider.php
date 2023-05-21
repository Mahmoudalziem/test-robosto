<?php

namespace Webkul\Core\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Core\Models\Channel::class,
        \Webkul\Core\Models\Country::class,
        \Webkul\Core\Models\CountryTranslation::class,
        \Webkul\Core\Models\CountryState::class,
        \Webkul\Core\Models\CountryStateTranslation::class,
        \Webkul\Core\Models\Locale::class,
        \Webkul\Core\Models\Setting::class,
        \Webkul\Core\Models\Tag::class,
        \Webkul\Core\Models\Complaint::class,
        \Webkul\Core\Models\Shelve::class,
        \Webkul\Core\Models\Sold::class,        
        \Webkul\Core\Models\ActivityLog::class,  
        \Webkul\Core\Models\SmsCampaign::class,
        \Webkul\Core\Models\SmsCampaignTag::class,
        \Webkul\Core\Models\SmsCampaignCustomer::class,        
        \Webkul\Core\Models\Alert::class, 
        \Webkul\Core\Models\AlertTranslation::class, 
        \Webkul\Core\Models\AlertAdmin::class,
        \Webkul\Core\Models\RetentionMessage::class,
        \Webkul\Core\Models\RetentionCustomer::class,
        \Webkul\Core\Models\BonusVariable::class
    ];
}
