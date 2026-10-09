<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create / update an order. `due_at` arrives as an ISO timestamp (the browser converts the admin's local time to UTC). */
class SaveOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_CUSTOMER)],
            'title' => ['required', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'due_at' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:60'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'action' => ['nullable', Rule::in(['draft', 'send'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'items.*.description' => 'item description',
            'items.*.quantity' => 'quantity',
            'items.*.unit_price' => 'price',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Choose a customer, or create a new one.',
            'items.required' => 'Add at least one line item.',
            'items.min' => 'Add at least one line item.',
        ];
    }

    public function orderData(): array
    {
        $data = $this->validated();
        $data['due_at'] = ! empty($data['due_at']) ? \Illuminate\Support\Carbon::parse($data['due_at'])->utc() : null;

        return $data;
    }
}
