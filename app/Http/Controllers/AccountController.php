<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAddressRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Order;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('account.index', compact('orders'));
    }

    public function orders()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('items.product')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('account.orders', compact('orders'));
    }

    public function order(Order $order)
    {
        abort_if($order->user_id !== auth()->id(), 403);

        $order->load('items.product', 'items.addons');

        return view('account.order', compact('order'));
    }

    public function editProfile()
    {
        return view('account.profile');
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        auth()->user()->update($request->validated());

        return back()->with('success', 'Profil mis à jour.');
    }

    public function editAddress()
    {
        return view('account.address');
    }

    public function updateAddress(UpdateAddressRequest $request)
    {
        auth()->user()->update($request->validated());

        return back()->with('success', 'Coordonnées mises à jour.');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        auth()->user()->update(['password' => $request->password]);

        return back()->with('success', 'Mot de passe modifié.');
    }
}
