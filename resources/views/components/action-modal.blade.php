{{-- Confirmation Modal (reusable for delete, etc.) --}}
@props(['id', 'title', 'formId', 'btnLabel' => 'Confirm', 'btnVariant' => 'primary'])

@php
    $variant = $btnVariant === 'critical' ? 'primary' : $btnVariant;
    $tone    = $btnVariant === 'critical' ? 'critical' : null;
@endphp

<ui-modal id="{{ $id }}">
    <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px;">
        {{ $slot }}
        <s-stack direction="inline" distribution="trailing" gap="base">
            <s-button variant="secondary" id="{{ $id }}-cancel">Cancel</s-button>
            <s-button variant="{{ $variant }}" @if($tone) tone="{{ $tone }}" @endif id="{{ $id }}-btn">{{ $btnLabel }}</s-button>
        </s-stack>
    </div>
    <ui-title-bar title="{{ $title }}"></ui-title-bar>
</ui-modal>
