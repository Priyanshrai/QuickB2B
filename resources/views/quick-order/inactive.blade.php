{{-- QuickB2B Inactive — shown when subscription expired --}}

@include('quick-order.partials.styles')

<div class="qb-app" style="display:flex;align-items:center;justify-content:center;min-height:60vh">
    <div style="text-align:center;max-width:400px;padding:40px 20px">
        <div style="font-size:48px;margin-bottom:16px">⏸️</div>
        <h2 style="font-size:20px;font-weight:700;color:#202223;margin:0 0 12px">QuickB2B is Currently Unavailable</h2>
        <p style="font-size:14px;color:#6d7175;line-height:1.6;margin:0 0 24px">
            This store's QuickB2B subscription is currently inactive.
            Please contact <strong>{{ $shopDomain }}</strong> to place your bulk order.
        </p>
        <p style="font-size:12px;color:#8d9298">Powered by QuickB2B</p>
    </div>
</div>
