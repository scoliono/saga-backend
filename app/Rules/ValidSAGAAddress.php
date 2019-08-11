<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ValidSAGAAddress implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Make a Validator
     *
     * @param array  $data
     * @return \Illuminate\Support\Facades\Validator
     */
    private static function validator($data)
    {
        // not much here for now
        return Validator::make($data, [
            'value' => 'required|string|size:42|starts_with:0x',
        ]);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return self::validator(compact('value'))->passes()
            && ctype_xdigit(substr($value, 2));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ':attribute must be a valid SAGA address.';
    }
}
