<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string',
            'shipping_address.address' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'required|string',
            'shipping_address.postal_code' => 'required|string',
            'shipping_address.phone' => 'required|string',
            'billing_address' => 'nullable|array',
            'billing_address.name' => 'required_with:billing_address|string',
            'billing_address.address' => 'required_with:billing_address|string',
            'billing_address.city' => 'required_with:billing_address|string',
            'billing_address.state' => 'required_with:billing_address|string',
            'billing_address.postal_code' => 'required_with:billing_address|string',
            'billing_address.phone' => 'required_with:billing_address|string',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ];
    }
}
