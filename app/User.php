<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'avatar',
        'btc', 'eth', 'email', 'password', 'gender',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'birthday' => 'datetime',
        'email_verified_at' => 'datetime',
        'verified' => 'boolean',
        'eth' => 'array',
    ];


    public function getTitle()
    {
        switch ($this->gender) {
            case 'm':
                return 'Mr.';
            case 'f':
                return 'Ms.';
            default:
                return null;
        }
    }

    public function getFullName()
    {
        if ($this->first_name && $this->last_name) {
            if ($this->middle_name) {
                return "{$this->first_name} {$this->middle_name} {$this->last_name}";
            } else {
                return "{$this->first_name} {$this->middle_name} {$this->last_name}";
            }
        } else if ($this->first_name) {
            return $this->first_name;
        } else if ($this->last_name) {
            return ($this->getTitle() ? $this->getTitle() . ' ' : null) . $this->last_name;
        }
    }

    public function toArray()
    {
        $arr = parent::toArray();
        if ($this->birthday) {
            $arr['birthday'] = date_format($this->birthday, 'Y-m-d');
        }
        return array_merge($arr, [
            'full_name' => $this->getFullName(),
        ]);
    }

    public function incomingTransactions()
    {
        return $this->hasMany('App\Transaction', 'from_id');
    }

    public function outgoingTransactions()
    {
        return $this->hasMany('App\Transaction', 'to_id');
    }
}
