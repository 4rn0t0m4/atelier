<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::where('is_featured', true)
            ->where('is_active', true)
            ->with(['category.parent', 'featuredImage'])
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as reviews_avg', 'rating')
            ->limit(8)
            ->get();

        if ($featuredProducts->isEmpty()) {
            $featuredProducts = Product::active()
                ->with(['category.parent', 'featuredImage'])
                ->withCount(['approvedReviews as reviews_count'])
                ->withAvg('approvedReviews as reviews_avg', 'rating')
                ->orderByDesc('total_sales')
                ->limit(8)
                ->get();
        }

        $categories = ProductCategory::root()
            ->where('slug', '!=', 'non-classe')
            ->with('featuredImage')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        return view('home', compact('featuredProducts', 'categories'));
    }
}
