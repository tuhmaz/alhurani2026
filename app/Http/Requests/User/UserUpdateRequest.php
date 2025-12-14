<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends BaseFormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $user = $this->route('user');

        return [
            'name'   => 'required|string|max:255',
            'email'  => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user?->id),
            ],
            'phone'  => 'nullable|string|max:15',
            'bio'    => 'nullable|string',
            'profile_photo' => 'nullable|image|max:2048',
        ];
    }
}

