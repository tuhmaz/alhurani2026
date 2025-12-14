<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class UserBulkDeleteRequest extends BaseFormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
        ];
    }
}

