<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PendingTransaction extends Model
{
    /**
     *  A Transaction that is sent to a guest user, which needs a
     *  customer_address supplied before it can be recorded.
     */

    protected $casts = [
        'value' => 'string',
        'receipt_list' => 'array',
    ];

    protected $fillable = [
        'from_name', 'from_email',
        'to_id', 'to_address', 'value', 'receipt_list'
    ];

    public function merchant()
    {
        return $this->hasOne('App\User', 'id', 'to_id');
    }
}
