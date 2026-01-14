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
            'job_title' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,pending,banned',
            'social_links' => 'nullable|array',
            'password' => 'nullable|string|min:8|confirmed',
            'profile_photo' => 'nullable|image|max:2048',
        ];
    }
}

