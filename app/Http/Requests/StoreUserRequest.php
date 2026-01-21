<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-\(\)]+$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.max' => 'First name may not be greater than 100 characters.',
            'last_name.required' => 'Last name is required.',
            'last_name.max' => 'Last name may not be greater than 100 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already in use.',
            'status.required' => 'Status is required.',
            'status.enum' => 'Please select a valid status.',
            'phone.regex' => 'Please enter a valid phone number.',
            'avatar.image' => 'Avatar must be an image file.',
            'avatar.mimes' => 'Avatar must be a JPEG, PNG, GIF, or WEBP image.',
            'avatar.max' => 'Avatar may not be larger than 2MB.',
        ];
    }
}
