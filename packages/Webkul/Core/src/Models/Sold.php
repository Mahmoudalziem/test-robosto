<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\Sold as SoldContract;

class Sold extends Model implements SoldContract {

    protected $fillable = ['area_id'];

    public function soldable() {
        return $this->morphTo();
    }
    

    public function newEloquentBuilder($query) {
        return new \Webkul\Core\Eloquent\BaseEloquentBuilder($query);
    }

}
