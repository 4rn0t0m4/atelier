<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\StockNotification;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $categories = ProductCategory::root()
            ->where('slug', '!=', 'non-classe')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        $query = Product::with(['category.parent', 'featuredImage'])
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as reviews_avg', 'rating')
            ->active()
            ->orderBy('name');

        $currentCategory = null;
        $currentTag = null;

        if ($request->filled('tag')) {
            $currentTag = \App\Models\ProductTag::where('slug', $request->tag)->first();
            if ($currentTag) {
                $query->whereHas('tags', fn ($q) => $q->where('product_tags.id', $currentTag->id));
            }
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(24)->withQueryString();

        if ($request->headers->get('Turbo-Frame') === 'products-grid') {
            return view('shop.partials.grid', compact('products', 'categories', 'currentCategory'));
        }

        // Produits vedettes (page principale sans filtre)
        $featuredProducts = collect();
        if (! $currentCategory && ! $currentTag && ! $request->filled('q')) {
            $featuredProducts = Product::where('is_featured', true)
                ->active()
                ->with(['category.parent', 'featuredImage'])
                ->withCount(['approvedReviews as reviews_count'])
                ->withAvg('approvedReviews as reviews_avg', 'rating')
                ->inRandomOrder()
                ->limit(4)
                ->get();
        }

        return view('shop.index', compact('products', 'categories', 'currentCategory', 'currentTag', 'featuredProducts'));
    }

    public function categoryOrProduct(string $parent, ?string $child = null)
    {
        if (! $child) {
            $category = ProductCategory::where('slug', $parent)->first();
            if ($category) {
                return $this->indexWithCategory($category);
            }

            return $this->showProduct($parent);
        }

        $childCategory = ProductCategory::where('slug', $child)
            ->whereHas('parent', fn ($q) => $q->where('slug', $parent))
            ->first();

        if ($childCategory) {
            return $this->indexWithCategory($childCategory);
        }

        return $this->showProduct($child, $parent);
    }

    public function show(string $parent, string $child, string $productSlug)
    {
        return $this->showProduct($productSlug, $child, $parent);
    }

    private function showProduct(string $slug, ?string $categorySlug = null, ?string $parentSlug = null)
    {
        $product = Product::where('slug', $slug)
            ->with(['category.parent', 'featuredImage'])
            ->active()
            ->firstOrFail();

        // Redirection canonique
        $canonicalUrl = $product->url();
        if (url()->current() !== $canonicalUrl) {
            return redirect($canonicalUrl, 301);
        }

        // Groupes d'addons
        $addonGroups = $product->getAllAddonGroups();

        // Galerie
        $galleryImages = collect();
        if ($product->gallery_image_ids) {
            $galleryImages = \App\Models\Media::whereIn('id', $product->gallery_image_ids)->get();
        }

        // Produits similaires
        $related = Product::with(['featuredImage', 'category.parent'])
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as reviews_avg', 'rating')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->limit(4)
            ->get();

        $reviews = $product->approvedReviews()->latest()->get();

        return view('shop.show', compact('product', 'addonGroups', 'galleryImages', 'related', 'reviews'));
    }

    private function indexWithCategory(ProductCategory $category)
    {
        $categories = ProductCategory::root()
            ->where('slug', '!=', 'non-classe')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        $query = Product::with(['category.parent', 'featuredImage'])
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as reviews_avg', 'rating')
            ->active()
            ->whereIn('category_id', $category->familyIds())
            ->orderBy('name');

        if (request()->filled('q')) {
            $search = request()->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(24)->withQueryString();
        $currentCategory = $category;

        if (request()->headers->get('Turbo-Frame') === 'products-grid') {
            return view('shop.partials.grid', compact('products', 'categories', 'currentCategory'));
        }

        return view('shop.index', compact('products', 'categories', 'currentCategory'));
    }

    public function storeReview(Request $request, Product $product)
    {
        $validated = $request->validate([
            'author_name' => 'required|string|max:100',
            'author_email' => 'required|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|max:2000',
        ]);

        $exists = ProductReview::where('product_id', $product->id)
            ->where('author_email', strtolower($validated['author_email']))
            ->exists();

        if ($exists) {
            return back()->with('review_error', 'Vous avez déjà laissé un avis pour ce produit.');
        }

        $user = auth()->user();

        ProductReview::create([
            ...$validated,
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'author_email' => strtolower($validated['author_email']),
        ]);

        return back()->with('review_success', 'Merci pour votre avis ! Il sera publié après validation.');
    }

    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::with(['category.parent', 'featuredImage'])
            ->active()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('short_description', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Product $p) => [
                'name' => $p->name,
                'url' => $p->url(),
                'price' => number_format($p->sale_price ?? $p->price, 2, ',', ' ') . ' €',
                'image' => $p->featuredImage?->url,
                'category' => $p->category?->name,
            ]);

        return response()->json($products);
    }
}
