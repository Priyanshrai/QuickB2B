@extends('shopify-app::layouts.default')

@section('content')

    @php
        $host = request('host');
        $shopDomain = Auth::user()->getDomain()->toNative();
        $homeUrl = URL::tokenRoute('home', compact('host'));
        $upgradeUrl = '/billing/1?host=' . $host . '&shop=' . $shopDomain;
        $isFree = !Auth::user()->plan_id && !Auth::user()->isFreemium() && !Auth::user()->isGrandfathered();
    @endphp

    <ui-title-bar title="QuickB2B > Plans">
        <button onclick="location.href='{{ $homeUrl }}'">← Dashboard</button>
    </ui-title-bar>

    @include('components.nav-menu')

    <s-page heading="Choose Your Plan">

        <s-stack gap="large-200">

            {{-- Flash Messages --}}
            @if(session('success'))
                <s-banner tone="success">{{ session('success') }}</s-banner>
            @endif
            @if(session('error'))
                <s-banner tone="critical">{{ session('error') }}</s-banner>
            @endif

            <s-stack direction="inline" gap="large-200">

                {{-- Free Plan --}}
                <s-section heading="🆓 Free">
                    <s-stack gap="base">
                        <s-paragraph tone="subdued">For small shops getting started.</s-paragraph>

                        <s-text as="p" variant="heading2xl" fontWeight="bold">$0<s-text as="span" variant="bodySm" tone="subdued" fontWeight="regular">/month</s-text></s-text>

                        <s-stack gap="small-200">
                            <s-text as="p">· Basic features</s-text>
                            <s-text as="p">· Limited operations</s-text>
                        </s-stack>

                        @if($isFree)
                            <s-badge tone="success">Current plan</s-badge>
                        @endif
                    </s-stack>
                </s-section>

                {{-- Pro Plan --}}
                <s-section heading="🚀 Pro">
                    <s-stack gap="base">
                        <s-paragraph tone="subdued">For growing businesses that need more power.</s-paragraph>

                        <s-text as="p" variant="heading2xl" fontWeight="bold">$9.99<s-text as="span" variant="bodySm" tone="subdued" fontWeight="regular">/month</s-text></s-text>

                        <s-paragraph tone="subdued">Cancel anytime</s-paragraph>

                        <s-stack gap="small-200">
                            <s-text as="p">· Unlimited operations</s-text>
                            <s-text as="p">· All features included</s-text>
                            <s-text as="p">· Priority support</s-text>
                        </s-stack>

                        @if(!$isFree)
                            <s-badge tone="success">Current plan</s-badge>
                            <s-button variant="danger" full-width onclick="location.href='{{ URL::tokenRoute('plans.cancel', ['host' => $host]) }}'">
                                Cancel Subscription
                            </s-button>
                        @else
                            <s-button variant="primary" full-width onclick="location.href='{{ $upgradeUrl }}'">
                                Subscribe Now →
                            </s-button>
                        @endif
                    </s-stack>
                </s-section>

            </s-stack>

            <s-banner tone="info">
                💡 You'll be redirected to Shopify to approve the subscription.
            </s-banner>

        </s-stack>

    </s-page>
@endsection
