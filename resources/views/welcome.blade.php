@extends('shopify-app::layouts.default')

@section('content')

    <ui-title-bar title="QuickB2B"></ui-title-bar>

    @include('components.nav-menu')

    <s-page heading="Dashboard" style="display:flex;flex-direction:column;gap:24px;">

        <s-banner status="info">
            🏪 <strong>{{ $shopDomain ?? Auth::user()->name }}</strong> — Welcome to QuickB2B
        </s-banner>

        {{-- One-Click Setup Section --}}
        <s-section heading="🚀 One-Click Setup">
            <s-paragraph tone="subdued" style="margin-bottom:16px;">
                This will create a <strong>"Quick Order"</strong> page on your storefront and add it to your navigation menu — all in one click.
            </s-paragraph>

            <form method="POST" action="{{ route('setup.create-page') }}">
                @csrf
                @sessionToken
                <input type="hidden" name="host" value="{{ request('host') }}">
                <s-button type="submit" variant="primary">
                    📄 Create Quick Order Page + Add to Menu
                </s-button>
            </form>

            @if (session('success'))
                <s-banner status="success" style="margin-top:16px;">
                    {{ session('success') }}
                </s-banner>
            @endif

            @if (session('error'))
                <s-banner status="critical" style="margin-top:16px;">
                    {{ session('error') }}
                </s-banner>
            @endif
        </s-section>

        <s-section heading="Getting Started">
            <s-paragraph tone="subdued">
                Your Shopify app is ready. Start building your features here.
            </s-paragraph>
        </s-section>

    </s-page>

@endsection

