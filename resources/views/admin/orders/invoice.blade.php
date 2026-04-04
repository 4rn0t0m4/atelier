<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $order->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .container { padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .logo { font-size: 20px; font-weight: bold; color: #92400e; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #92400e; margin-bottom: 5px; }
        .meta { color: #666; font-size: 11px; }
        .addresses { width: 100%; margin-bottom: 30px; }
        .addresses td { width: 50%; vertical-align: top; padding: 15px; }
        .address-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin-bottom: 8px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th { background: #f8f4f0; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; border-bottom: 2px solid #e5e0db; }
        table.items td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        table.items .right { text-align: right; }
        .addon { color: #888; font-size: 10px; padding-left: 10px; }
        .totals { width: 280px; margin-left: auto; }
        .totals td { padding: 6px 0; }
        .totals .label { color: #666; }
        .totals .value { text-align: right; }
        .totals .total-row td { border-top: 2px solid #92400e; padding-top: 10px; font-size: 14px; font-weight: bold; color: #92400e; }
        .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 10px; color: #999; }
    </style>
</head>
<body>
<div class="container">

    {{-- Header --}}
    <table style="width: 100%; margin-bottom: 40px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="logo">Atelier d'Aubin</div>
                <div class="meta" style="margin-top: 5px;">
                    www.atelier-aubin.fr
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <div class="invoice-title">FACTURE</div>
                <div class="meta">
                    @if($order->invoice_number)
                        N° {{ $order->invoice_number }}<br>
                    @endif
                    Date : {{ $order->created_at->format('d/m/Y') }}<br>
                    @if($order->paid_at)
                        Payée le : {{ $order->paid_at->format('d/m/Y') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Addresses --}}
    <table class="addresses">
        <tr>
            <td style="background: #faf8f5; border-radius: 8px;">
                <div class="address-label">Facturer a</div>
                <strong>{{ $order->billing_first_name }} {{ $order->billing_last_name }}</strong><br>
                {{ $order->billing_address_1 }}<br>
                @if($order->billing_address_2){{ $order->billing_address_2 }}<br>@endif
                {{ $order->billing_postcode }} {{ $order->billing_city }}<br>
                {{ $order->billing_country }}<br>
                @if($order->billing_phone)Tel: {{ $order->billing_phone }}<br>@endif
                {{ $order->billing_email }}
            </td>
            <td style="background: #faf8f5; border-radius: 8px;">
                <div class="address-label">Livrer a</div>
                @if($order->shipping_address_1)
                    <strong>{{ $order->shipping_first_name }} {{ $order->shipping_last_name }}</strong><br>
                    {{ $order->shipping_address_1 }}<br>
                    @if($order->shipping_address_2){{ $order->shipping_address_2 }}<br>@endif
                    {{ $order->shipping_postcode }} {{ $order->shipping_city }}<br>
                    {{ $order->shipping_country }}
                @else
                    <em>Identique a la facturation</em>
                @endif
                @if($order->relay_point_code)
                    <br><br>Point relais : {{ $order->relay_point_code }}
                @endif
            </td>
        </tr>
    </table>

    {{-- Items --}}
    <table class="items">
        <thead>
            <tr>
                <th>Produit</th>
                <th class="right">Prix unit.</th>
                <th class="right">Qté</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->sku)<br><span style="color: #999; font-size: 10px;">SKU: {{ $item->sku }}</span>@endif
                        @foreach($item->addons as $addon)
                            <br><span class="addon">+ {{ $addon->group_name }}: {{ $addon->addon_name }}
                                @if($addon->price > 0)({{ number_format($addon->price, 2, ',', ' ') }} &euro;)@endif
                            </span>
                        @endforeach
                    </td>
                    <td class="right">{{ number_format($item->unit_price, 2, ',', ' ') }} &euro;</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format($item->total, 2, ',', ' ') }} &euro;</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals">
        <tr>
            <td class="label">Sous-total</td>
            <td class="value">{{ number_format($order->subtotal, 2, ',', ' ') }} &euro;</td>
        </tr>
        @if($order->discount_total > 0)
            <tr>
                <td class="label">Remise{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td>
                <td class="value" style="color: green;">-{{ number_format($order->discount_total, 2, ',', ' ') }} &euro;</td>
            </tr>
        @endif
        <tr>
            <td class="label">Livraison ({{ $order->shipping_method ?? '-' }})</td>
            <td class="value">
                @if($order->shipping_total > 0)
                    {{ number_format($order->shipping_total, 2, ',', ' ') }} &euro;
                @else
                    Gratuit
                @endif
            </td>
        </tr>
        <tr class="total-row">
            <td>Total TTC</td>
            <td class="value">{{ number_format($order->total, 2, ',', ' ') }} &euro;</td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        Atelier d'Aubin &mdash; TVA non applicable, article 293 B du CGI<br>
        Merci pour votre commande !
    </div>

</div>
</body>
</html>
