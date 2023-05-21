<?php

namespace Webkul\Core\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class BaseEloquentBuilder extends Builder
{


    // other shared scopes...
    public function byArea()
    {
        $area_id = Schema::hasColumn($this->model->getTable(), 'area_id') ? 'area_id' : 'id';
        $user = auth('admin')->user();
        if ($user) {
            if (!$user->hasRole(['super-admin', 'operation-manager'])) {
                return $this->whereIn($area_id, $user->areas->pluck('id'));
            }
            return $this;
        }
    }


    // custom filter for Inventory Transaction(Transfer)
    public function fromAreaToAreaValidation()
    {
        $user = auth('admin')->user();
        if (!$user->hasRole(['super-admin', 'operation-manager'])) {
            return $this->whereIn('from_area_id', $user->areas->pluck('id'))->orWhereIn('to_area_id', $user->areas->pluck('id'));
        }
        return $this;
    }
}
