<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class UserBulkUpdateStatusRequest extends BaseFormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:users,id',
            'status' => 'required|string|in:active,inactive,pending,banned',
        ];
    }
}
