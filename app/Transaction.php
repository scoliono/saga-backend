<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $incrementing = false;

    protected $casts = [
        // Use bcmath to do precise calculations with currency
        'value' => 'string',
        'receipt_list' => 'array',
    ];

    protected $fillable = [
        'id',
        'from_name', 'from_email', 'from_id', 'from_address',
        'to_id', 'to_address', 'value', 'receipt_list'
    ];


    public function completed()
    {
        return $this->tx_hash !== null;
    }

    public function customer()
    {
        return $this->hasOne('App\User', 'id', 'from_id');
    }

    public function merchant()
    {
        return $this->hasOne('App\User', 'id', 'to_id');
    }
}
