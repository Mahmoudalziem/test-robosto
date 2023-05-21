<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\AdminProxy;
use Webkul\Core\Models\AlertAdminProxy;
use Webkul\Core\Contracts\Alert as AlertContract;
use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Alert extends Model implements AlertContract, TranslatableContract {

    use Translatable;

    protected $fillable = ['admin_type', 'key','model', 'model_id', 'direct_to'];
    protected $casts = [
        'admin_type' => 'json',
    ];
    public $translatedAttributes = [
        'title', 'body'
    ];

    public function admins() {
        return $this->belongsToMany(AdminProxy::modelClass(),'alert_admins')->withTimestamps();
    }
    
    public function me() {
        return $this->hasOne(AlertAdminProxy::modelClass() ) ;
    }    

}
