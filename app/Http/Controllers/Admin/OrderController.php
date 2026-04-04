<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderShipped;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\BoxtalShippingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (! $request->filled('search')) {
            $query->where('status', 'processing');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('billing_email', 'like', "%{$search}%")
                    ->orWhere('billing_last_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        $paidStatuses = ['processing', 'shipped', 'completed'];
        $monthStart = now()->startOfMonth();
        $metrics = [
            'total_orders' => Order::whereIn('status', $paidStatuses)->where('created_at', '>=', $monthStart)->count(),
            'revenue' => Order::whereIn('status', $paidStatuses)->where('created_at', '>=', $monthStart)->sum('total'),
            'items_sold' => OrderItem::whereHas('order', fn ($q) => $q->whereIn('status', $paidStatuses)->where('created_at', '>=', $monthStart))->sum('quantity'),
            'average_order' => Order::whereIn('status', $paidStatuses)->where('created_at', '>=', $monthStart)->avg('total') ?: 0,
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
        ];

        return view('admin.orders.index', compact('orders', 'metrics'));
    }

    public function show(Request $request, Order $order)
    {
        $order->load(['user', 'items.product', 'items.addons']);

        if ($request->ajax()) {
            return view('admin.orders._detail', compact('order'));
        }

        return view('admin.orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $order->load(['user', 'items.product']);

        return view('admin.orders.edit', compact('order'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,processing,shipped,completed,cancelled,refunded',
            'tracking_number' => 'nullable|string|max:255',
            'tracking_carrier' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:500',
            'customer_note' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $order->status;

        $order->update($validated);

        // Send shipped email when tracking number is added
        if (($validated['tracking_number'] ?? null) && !$order->shipped_at) {
            $order->update(['shipped_at' => now()]);

            try {
                $order->load('items');
                Mail::to($order->billing_email)->send(new OrderShipped($order));
                Mail::to(config('mail.admin_address'))->send(new OrderShipped($order));
            } catch (\Exception $e) {
                Log::error("Erreur envoi email expédition #{$order->number}", ['error' => $e->getMessage()]);
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => 'Commande mise à jour.', 'order_id' => $order->id]);
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Commande mise à jour.');
    }

    public function invoice(Order $order)
    {
        $order->load(['items.addons']);

        $pdf = Pdf::loadView('admin.orders.invoice', compact('order'));
        $pdf->setPaper('a4');

        $filename = $order->invoice_number ? "facture-{$order->invoice_number}.pdf" : "facture-{$order->number}.pdf";

        return $pdf->download($filename);
    }

    public function createShipment(Request $request, Order $order, BoxtalShippingService $boxtal)
    {
        if ($order->boxtal_shipping_order_id) {
            $msg = 'Une expédition Boxtal existe déjà pour cette commande.';

            return $request->ajax()
                ? response()->json(['error' => $msg, 'order_id' => $order->id])
                : redirect()->route('admin.orders.show', $order)->with('error', $msg);
        }

        $validated = $request->validate([
            'weight' => 'nullable|numeric|min:0.01|max:30',
            'length' => 'nullable|integer|min:1|max:200',
            'width' => 'nullable|integer|min:1|max:200',
            'height' => 'nullable|integer|min:1|max:200',
            'shippingOfferCode' => 'nullable|string|max:50',
        ]);

        $overrides = array_filter($validated);

        $result = $boxtal->createShipment($order, $overrides);

        if ($result['success']) {
            $updateData = ['boxtal_shipping_order_id' => $result['shipping_order_id']];
            if (! empty($result['label_url'])) {
                $updateData['boxtal_label_url'] = $result['label_url'];
            }
            $order->update($updateData);

            $msg = 'Expédition Boxtal créée (ID : ' . $result['shipping_order_id'] . ').';

            return $request->ajax()
                ? response()->json(['success' => $msg, 'order_id' => $order->id])
                : redirect()->route('admin.orders.show', $order)->with('success', $msg);
        }

        $msg = 'Erreur Boxtal : ' . $result['error'];

        return $request->ajax()
            ? response()->json(['error' => $msg, 'order_id' => $order->id])
            : redirect()->route('admin.orders.show', $order)->with('error', $msg);
    }

    public function label(Order $order, BoxtalShippingService $boxtal)
    {
        if (! $order->boxtal_shipping_order_id) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Aucune expédition Boxtal pour cette commande.');
        }

        $labelUrl = $order->boxtal_label_url ?: $boxtal->fetchLabelUrl($order->boxtal_shipping_order_id);

        if ($labelUrl) {
            if (! $order->boxtal_label_url) {
                $order->update(['boxtal_label_url' => $labelUrl]);
            }

            return redirect()->away($labelUrl);
        }

        return redirect()->route('admin.orders.show', $order)->with('error', 'Étiquette non disponible. Vérifiez sur le dashboard Boxtal.');
    }

    public function resetShipment(Request $request, Order $order)
    {
        $order->update([
            'boxtal_shipping_order_id' => null,
            'boxtal_label_url' => null,
        ]);

        $msg = 'Expédition Boxtal dissociée.';

        return $request->ajax()
            ? response()->json(['success' => $msg, 'order_id' => $order->id])
            : redirect()->route('admin.orders.show', $order)->with('success', $msg);
    }

    public function destroy(Request $request, Order $order)
    {
        if (!in_array($order->status, ['pending', 'cancelled'])) {
            $msg = 'Seules les commandes non réglées ou annulées peuvent être supprimées.';

            return $request->ajax()
                ? response()->json(['error' => $msg])
                : redirect()->route('admin.orders.show', $order)->with('error', $msg);
        }

        // Delete addons, then items, then order
        foreach ($order->items as $item) {
            $item->addons()->delete();
        }
        $order->items()->delete();
        $order->delete();

        if ($request->ajax()) {
            return response()->json(['success' => 'Commande supprimée.', 'deleted' => true]);
        }

        return redirect()->route('admin.orders.index')->with('success', 'Commande supprimée.');
    }
}
