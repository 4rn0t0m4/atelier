<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        $items = $this->cart->itemsWithProducts();

        $cartProductIds = array_column(array_values($items), 'product_id');

        $suggestions = Product::with('featuredImage')
            ->active()
            ->where('stock_status', '!=', 'outofstock')
            ->when(! empty($cartProductIds), fn ($q) => $q->whereNotIn('id', $cartProductIds))
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->take(4)
            ->get();

        return view('cart.index', [
            'items' => $items,
            'subtotal' => $this->cart->subtotal(),
            'suggestions' => $suggestions,
        ]);
    }

    public function miniCart()
    {
        return view('cart.partials.mini-cart', [
            'items' => $this->cart->itemsWithProducts(),
            'subtotal' => $this->cart->subtotal(),
            'count' => $this->cart->count(),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:99',
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = (int) $request->input('quantity', 1);
        $addons = $request->input('addons', []);

        $this->cart->add($product, $quantity, $addons);

        if ($request->wantsTurboStream()) {
            return response()->turboStream([
                response()->turboStream()
                    ->target('cart-count')
                    ->action('update')
                    ->view('cart.partials.count', ['count' => $this->cart->count()]),
                response()->turboStream()
                    ->target('cart-flash')
                    ->action('update')
                    ->view('cart.partials.flash-added', ['product' => $product]),
            ]);
        }

        return back()->with('success', "« {$product->name} » ajouté au panier.");
    }

    public function update(Request $request, string $key)
    {
        $request->validate(['quantity' => 'required|integer|min:0|max:99']);

        $this->cart->update($key, (int) $request->quantity);

        if ($request->wantsTurboStream()) {
            $items = $this->cart->all();

            if (empty($items)) {
                return response()->turboStream([
                    response()->turboStream()->target("cart-item-{$key}")->action('remove'),
                    response()->turboStream()
                        ->target('cart-subtotal')
                        ->action('update')
                        ->view('cart.partials.subtotal', ['subtotal' => 0]),
                    response()->turboStream()
                        ->target('cart-count')
                        ->action('update')
                        ->view('cart.partials.count', ['count' => 0]),
                ]);
            }

            return response()->turboStream([
                response()->turboStream()
                    ->target("cart-item-{$key}")
                    ->action('update')
                    ->view('cart.partials.item', [
                        'item' => $items[$key] ?? null,
                        'subtotal' => $this->cart->subtotal(),
                    ]),
                response()->turboStream()
                    ->target('cart-subtotal')
                    ->action('update')
                    ->view('cart.partials.subtotal', ['subtotal' => $this->cart->subtotal()]),
                response()->turboStream()
                    ->target('cart-count')
                    ->action('update')
                    ->view('cart.partials.count', ['count' => $this->cart->count()]),
            ]);
        }

        return redirect()->route('cart.index');
    }

    public function remove(string $key)
    {
        $this->cart->remove($key);

        if (request()->wantsTurboStream()) {
            return response()->turboStream([
                response()->turboStream()->target("cart-item-{$key}")->action('remove'),
                response()->turboStream()
                    ->target('cart-subtotal')
                    ->action('update')
                    ->view('cart.partials.subtotal', ['subtotal' => $this->cart->subtotal()]),
                response()->turboStream()
                    ->target('cart-count')
                    ->action('update')
                    ->view('cart.partials.count', ['count' => $this->cart->count()]),
            ]);
        }

        return redirect()->route('cart.index');
    }
}
