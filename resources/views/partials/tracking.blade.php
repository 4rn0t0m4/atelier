{{-- Google Analytics 4 (gtag.js) --}}
@if(config('tracking.google_analytics_id'))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ config('tracking.google_analytics_id') }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ config('tracking.google_analytics_id') }}');
</script>
@endif

{{-- Facebook Pixel --}}
@if(config('tracking.facebook_pixel_id'))
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ config('tracking.facebook_pixel_id') }}');
    fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ config('tracking.facebook_pixel_id') }}&ev=PageView&noscript=1"/></noscript>
@endif

{{-- Google Merchant Center (verification via gtag, le flux produit se configure dans GMC) --}}
@if(config('tracking.google_merchant_id'))
<meta name="google-site-verification" content="{{ config('tracking.google_merchant_id') }}">
@endif
