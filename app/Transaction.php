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
        'to_name', 'to_email', 'id', 'from_id', 'to_address', 'value', 'receipt_list'
    ];


    public function sender()
    {
        return $this->hasOne('App\User', 'id', 'from_id');
    }
}
