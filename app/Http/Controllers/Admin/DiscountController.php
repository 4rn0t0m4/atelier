<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountRule;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index()
    {
        $discounts = DiscountRule::latest()->paginate(20);

        return view('admin.discounts.index', compact('discounts'));
    }

    public function create()
    {
        $categories = ProductCategory::orderBy('name')->get();

        return view('admin.discounts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRule($request);

        DiscountRule::create($validated);

        return redirect()->route('admin.discounts.index')->with('success', 'Réduction créée.');
    }

    public function edit(DiscountRule $discount)
    {
        $categories = ProductCategory::orderBy('name')->get();

        return view('admin.discounts.edit', compact('discount', 'categories'));
    }

    public function update(Request $request, DiscountRule $discount)
    {
        $validated = $this->validateRule($request, $discount);

        $discount->update($validated);

        return redirect()->route('admin.discounts.index')->with('success', 'Réduction mise à jour.');
    }

    public function destroy(DiscountRule $discount)
    {
        $discount->delete();

        return redirect()->route('admin.discounts.index')->with('success', 'Réduction supprimée.');
    }

    private function validateRule(Request $request, ?DiscountRule $discount = null): array
    {
        $uniqueCode = $discount ? ",{$discount->id}" : '';

        return $request->validate([
            'name' => 'required|string|max:255',
            'coupon_code' => "nullable|string|max:50|unique:discount_rules,coupon_code{$uniqueCode}",
            'type' => 'required|in:coupon,automatic',
            'discount_type' => 'required|in:percentage,fixed_cart',
            'discount_amount' => 'required|numeric|min:0',
            'target_categories' => 'nullable|array',
            'target_categories.*' => 'exists:product_categories,id',
            'min_cart_value' => 'nullable|numeric|min:0',
            'max_cart_value' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|integer|min:0',
            'max_quantity' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'stackable' => 'boolean',
            'free_shipping' => 'boolean',
            'exclude_sale_items' => 'boolean',
        ]);
    }
}
