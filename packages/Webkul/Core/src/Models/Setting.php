<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Contracts\Setting as SettingContract;

class Setting extends Model implements SettingContract
{
    protected $fillable = [
        'key',
        'value',
        'icon',
        'group',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];
}