<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class UserStoreRequest extends BaseFormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|string|min:8|confirmed',
            'role'      => 'required|string',
        ];
    }
}

