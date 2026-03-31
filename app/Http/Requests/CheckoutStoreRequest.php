<?php

namespace App\Http\Requests;

use App\Services\OrderService;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shippingKeys = implode(',', array_keys(config('shipping.methods')));
        $countryKeys = implode(',', array_keys(config('shipping.countries')));

        return [
            'billing_first_name' => 'required|string|max:100',
            'billing_last_name' => 'required|string|max:100',
            'billing_email' => 'required|email|max:255',
            'billing_phone' => 'nullable|string|max:30',
            'billing_address_1' => 'required|string|max:255',
            'billing_address_2' => 'nullable|string|max:255',
            'billing_city' => 'required|string|max:100',
            'billing_postcode' => 'required|string|max:20',
            'billing_country' => 'required|string|in:'.$countryKeys,
            'shipping_same' => 'nullable|boolean',
            'shipping_first_name' => 'nullable|string|max:100',
            'shipping_last_name' => 'nullable|string|max:100',
            'shipping_address_1' => 'nullable|string|max:255',
            'shipping_address_2' => 'nullable|string|max:255',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_postcode' => 'nullable|string|max:20',
            'shipping_country' => 'nullable|string|in:'.$countryKeys,
            'customer_note' => 'nullable|string|max:1000',
            'shipping_method' => 'required|in:'.$shippingKeys,
            'relay_point_code' => 'nullable|required_if:shipping_method,boxtal,boxtal_intl|string|max:100',
            'relay_point_name' => 'nullable|string|max:255',
            'relay_point_address' => 'nullable|string|max:500',
            'relay_network' => 'nullable|string|max:50',
            'coupon_code' => 'nullable|string|max:50',
            'payment_method' => 'required|in:stripe,paypal',
            'create_account' => 'nullable|boolean',
            'password' => 'nullable|required_if:create_account,1|string|min:8|confirmed',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $country = $this->boolean('shipping_same', false)
                ? $this->billing_country
                : ($this->shipping_country ?? $this->billing_country);

            $orderService = app(OrderService::class);
            $allowed = array_keys($orderService->availableMethodsForCountry($country ?? 'FR'));

            if ($this->shipping_method && ! in_array($this->shipping_method, $allowed)) {
                $validator->errors()->add('shipping_method', 'Ce mode de livraison n\'est pas disponible pour le pays sélectionné.');
            }

            // Vérifier que l'email n'est pas déjà associé à un compte
            if ($this->boolean('create_account') && ! auth()->check()) {
                $exists = \App\Models\User::where('email', $this->billing_email)->exists();
                if ($exists) {
                    $validator->errors()->add('billing_email', 'Un compte existe déjà avec cette adresse e-mail. Connectez-vous ou utilisez une autre adresse.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'billing_first_name.required' => 'Le prénom est obligatoire.',
            'billing_last_name.required' => 'Le nom est obligatoire.',
            'billing_email.required' => 'L\'adresse e-mail est obligatoire.',
            'billing_email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'billing_address_1.required' => 'L\'adresse est obligatoire.',
            'billing_city.required' => 'La ville est obligatoire.',
            'billing_postcode.required' => 'Le code postal est obligatoire.',
            'billing_country.required' => 'Le pays est obligatoire.',
            'shipping_method.required' => 'Veuillez choisir un mode de livraison.',
            'relay_point_code.required_if' => 'Veuillez sélectionner un point relais.',
            'password.required_if' => 'Le mot de passe est obligatoire pour créer un compte.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ];
    }
}
