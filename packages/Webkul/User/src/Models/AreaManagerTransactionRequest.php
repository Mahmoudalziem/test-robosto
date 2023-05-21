<?php

namespace Webkul\User\Models;

use Webkul\Area\Models\AreaProxy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\User\Contracts\AreaManagerTransactionRequest as AreaManagerTransactionRequestContract;

class AreaManagerTransactionRequest extends Model implements AreaManagerTransactionRequestContract
{
    public const STATUS_PENDING           = 'pending';
    public const STATUS_RECEIVED           = 'received';
    public const STATUS_CANCELLED          = 'cancelled';

    protected $table = 'area_manager_transaction_requests';

    protected $fillable = ['area_id', 'area_manager_id', 'accountant_id', 'amount', 'transaction_id', 'transaction_date'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        return Storage::url($this->image);
    }

    public function area()
    {
        return $this->belongsTo(AreaProxy::modelClass());
    }

    public function areaManager()
    {
        return $this->belongsTo(AreaManagerWalletProxy::modelClass(), 'area_manager_id','area_manager_id');
    }

    public function accountant()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'accountant_id');
    }
    
    public function tickets()
    {
        return $this->hasMany(TransactionTicketProxy::modelClass(), 'transaction_id');
    }
}
