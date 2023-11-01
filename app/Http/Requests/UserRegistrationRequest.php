<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            'first_name'=>'required|min:4',
            'last_name'=>'required|min:4',
            'email' => 'required|email|unique:users,email',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            'profile_pic' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'time_zone' => 'string|nullable|sometimes',
        ];
    }
}
