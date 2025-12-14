<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class UserUpdateRolesPermissionsRequest extends BaseFormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'roles'       => 'nullable|array',
            'permissions' => 'nullable|array',
        ];
    }
}

