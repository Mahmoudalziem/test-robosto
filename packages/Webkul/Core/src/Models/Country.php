<?php

namespace Webkul\Core\Models;

use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Core\Contracts\Country as CountryContract;
use Webkul\Supplier\Models\SupplierProxy;

class Country extends TranslatableModel implements CountryContract
{
    public $timestamps = false;

    public $translatedAttributes = ['name'];

    protected $with = ['translations'];

    /**
     * the suppliers the belongs to the country
     */
    public function suppliers()
    {
        return $this->hasMany(SupplierProxy::modelClass());
    }
}