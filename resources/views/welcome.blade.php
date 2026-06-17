@extends('shopify-app::layouts.default')

@section('content')

    <ui-title-bar title="QuickB2B"></ui-title-bar>

    @include('components.nav-menu')

    <s-page heading="Dashboard" style="display:flex;flex-direction:column;gap:24px;">

        <s-banner status="info">
            🏪 <strong>{{ $shopDomain ?? Auth::user()->name }}</strong> — Welcome to QuickB2B
        </s-banner>

        <s-section heading="Getting Started">
            <s-paragraph tone="subdued">
                Your Shopify app is ready. Start building your features here.
            </s-paragraph>
        </s-section>

    </s-page>

@endsection

