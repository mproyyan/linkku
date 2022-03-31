<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => 'required|max:50|regex:/^[a-zA-Z\s]+$/',
            'username' => 'required|min:5|max:15|unique:users|regex:/^[a-z0-9_.]+$/',
            'email' => 'required|unique:users',
            'password' => 'required|min:8|max:255|confirmed'
        ];
    }

    public function messages()
    {
        return [
            'name.regex' => 'Name field only contain letters',
            'username.regex' => 'Username only contains lowercase, number, dot and underscore'
        ];
    }
}
