<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Creating / editing a customer. Also used by the "new customer" panel inside the order form (AJAX). */
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => preg_replace('/\D+/', '', (string) $this->input('phone')) ?: null,
        ]);
    }

    public function rules(): array
    {
        $ignore = $this->route('customer')?->id;

        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($ignore)],
            'phone_country' => ['nullable', 'required_with:phone', Rule::in(array_keys(config('countries')))],
            'phone' => ['nullable', 'digits_between:4,14'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['email.unique' => 'A customer with this e-mail address already exists.'];
    }

    /** Fields ready to save: the phone is stored as "+<dial code><number>". */
    public function customerData(): array
    {
        $data = $this->validated();
        $country = $data['phone_country'] ?? null;
        $data['phone'] = ! empty($data['phone']) && $country ? '+' . config("countries.$country.1") . ltrim($data['phone'], '0') : null;
        $data['phone_country'] = $data['phone'] ? $country : null;

        return $data;
    }
}
