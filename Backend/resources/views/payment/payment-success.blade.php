<?php
define('PAY_PAGE_CONFIG', config('paypage-payment'));
$configData = configItem();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - OnlinePuja.live</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

@php
    $gtmId = \App\Brain\Vault::get('gtm_container_id');
    $ga4Id = \App\Brain\Vault::get('ga4_measurement_id', 'G-7T63JY1D93');
    $metaPixelId = \App\Brain\Vault::get('meta_pixel_id') ?: (\App\Brain\Vault::get('fb_app_id') ?: '1738490804042986');
    $gAdsId = \App\Brain\Vault::get('google_ads_id');
    $gAdsLabel = \App\Brain\Vault::get('google_ads_label');
    $resolvedUser = $user ?? (authcheck() ?: null);
    $activePayment = $payment ?? ($paymentcall ?? null);
    $paidValue = $activePayment ? (float)($activePayment->amount ?? 1500) : 1500.0;
    $orderId = 'OP_' . ($activePayment->id ?? time());

    $userEmail = '';
    $userPhone = '';
    if ($resolvedUser) {
        $userEmail = is_array($resolvedUser) ? ($resolvedUser['email'] ?? '') : ($resolvedUser->email ?? '');
        $userPhone = is_array($resolvedUser) ? ($resolvedUser['contactNo'] ?? '') : ($resolvedUser->contactNo ?? '');
    }
@endphp

@if(!empty($gtmId))
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
<!-- End Google Tag Manager -->

<!-- GTM Enhanced Ecommerce DataLayer Purchase Push -->
<script>
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ ecommerce: null });
  window.dataLayer.push({
    'event': 'purchase',
    'ecommerce': {
      'transaction_id': '{{ $orderId }}',
      'value': {{ $paidValue }},
      'currency': 'INR',
      'items': [{
        'item_id': '{{ $orderId }}',
        'item_name': 'Vedic Consultation / Puja Service',
        'price': {{ $paidValue }},
        'quantity': 1
      }]
    }
  });
</script>
@endif

@if(!empty($ga4Id) || !empty($gAdsId))
<!-- Global site tag (gtag.js) - Google Analytics 4 & Google Ads -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id ?: $gAdsId }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  @if(!empty($ga4Id))
  gtag('config', '{{ $ga4Id }}');
  @endif

  @if(!empty($gAdsId))
  gtag('config', '{{ $gAdsId }}');
  @endif

  @if(!empty($userEmail) || !empty($userPhone))
  // Google Ads Enhanced Conversions (First-party user data)
  gtag('set', 'user_data', {
    @if(!empty($userEmail))
    email: '{{ addslashes($userEmail) }}',
    @endif
    @if(!empty($userPhone))
    phone_number: '{{ addslashes($userPhone) }}'
    @endif
  });
  @endif

  // Google Analytics 4 & Google Ads Purchase Event
  gtag('event', 'purchase', {
    transaction_id: '{{ $orderId }}',
    value: {{ $paidValue }},
    currency: 'INR',
    items: [{
      item_id: '{{ $orderId }}',
      item_name: 'Vedic Consultation / Puja Service',
      price: {{ $paidValue }},
      quantity: 1
    }]
  });

  // Dedicated Google Ads Conversion Event
  @if(!empty($gAdsId) && !empty($gAdsLabel))
  gtag('event', 'conversion', {
    'send_to': '{{ $gAdsId }}/{{ $gAdsLabel }}',
    'value': {{ $paidValue }},
    'currency': 'INR',
    'transaction_id': '{{ $orderId }}'
  });
  @endif
</script>
@endif

@if(!empty($metaPixelId))
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{{ $metaPixelId }}');
fbq('track', 'PageView');
fbq('track', 'Purchase', {
    content_name: 'Vedic Consultation / Puja Service',
    content_ids: ['{{ $orderId }}'],
    value: {{ $paidValue }},
    currency: 'INR'
});
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=Purchase&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
@endif
</head>

@php
    if(astroauthcheck()){
        $redirect_url = route('front.getAstrologerWallet');
    } else {
        $redirect_url = route('front.getMyWallet');
    }
@endphp

<body>
@if(!empty($gtmId))
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif

    <div class="container text-center mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 p-5 shadow-sm rounded bg-white">
                <i class="fa fa-check-circle fa-5x text-success mb-3"></i>
                <h2 class="font-weight-bold text-dark">Payment Successful! 🙏</h2>
                <p class="text-muted">Your transaction of <strong>₹{{ number_format($paidValue, 2) }}</strong> has been verified. Divine blessings are on the way.</p>
                <div class="alert alert-success py-2 mt-3 font-weight-bold">
                    Redirecting in <span id="countdown">4</span> seconds...
                </div>
                <a href="<?= $redirect_url ?>" class="btn btn-warning btn-block font-weight-bold py-2 mt-3" style="background:#f59e0b; border:none; color:#020617;">
                    Return to Wallet & Services
                </a>
            </div>
        </div>
    </div>

    <script>
        var count = 4;
        var countdown = setInterval(function(){
            count--;
            var el = document.getElementById('countdown');
            if(el) el.innerText = count;
            if (count <= 0) {
                clearInterval(countdown);
                window.location.href = "<?= $redirect_url ?>";
            }
        }, 1000);
    </script>
</body>
</html>