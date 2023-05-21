<?php

namespace Webkul\Driver\Builders;

use Webkul\Driver\Models\Driver;
use Illuminate\Database\Eloquent\Builder;

class DriverBuilder extends Builder{

    public function whereNot($model) {
        $this->whereKeyNot($model->getKey());

        return $this;
    }

    // other shared scopes...

    public function userRole() {
        $user = auth()->user();
        if ($user->hasRole(['super-admin', 'operation-manager'])) {
            return $this->where('area_id', $user->areas()->first()->id);
        }

        return $this;
    }

}
