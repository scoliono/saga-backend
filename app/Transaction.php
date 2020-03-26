<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $incrementing = true;

    protected $casts = [
        'value' => 'string',
        'receipt_list' => 'array',
        'discount_list' => 'array',
    ];

    protected $fillable = [
        'from_name', 'from_email', 'from_id', 'from_address',
        'to_id', 'to_address', 'value', 'receipt_list',
        'discount_list', 'memo'
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

    public function toPublicArray()
    {
        $arr = $this->toArray();
        $arr['merchant'] = $this->merchant ? $this->merchant->toPublicArray() : null;
        $arr['customer'] = $this->customer ? $this->customer->toPublicArray() : null;
        return $arr;
    }
}
