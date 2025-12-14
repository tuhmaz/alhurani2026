<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

class RegisterRequest extends BaseFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}

