<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\TransactionTicket as TransactionTicketContract;

class TransactionTicket extends Model implements TransactionTicketContract
{
    protected $table = 'transaction_tickets';

    protected $fillable = ['transaction_id', 'sender_id', 'note'];

    public function transaction()
    {
        return $this->belongsTo(AreaManagerTransactionRequestProxy::modelClass(), 'transaction_id');
    }
    
    
    public function sender()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'sender_id');
    }
}