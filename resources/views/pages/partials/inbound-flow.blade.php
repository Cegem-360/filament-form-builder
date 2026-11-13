@php
    $steps = [
        ['flow_external', 'flow_external_sub'],
        ['flow_url', 'flow_url_sub'],
        ['flow_mapping', 'flow_mapping_sub'],
        ['flow_result', 'flow_result_sub'],
    ];
@endphp
<div class="ffb-help-flow" role="img"
     aria-label="{{ collect($steps)->map(fn ($s) => __('filament-form-builder::form.inbound_help.'.$s[0]))->join(' → ') }}">
    @foreach ($steps as [$title, $sub])
        <div @class(['ffb-help-flow-step', 'ffb-help-flow-step-final' => $loop->last])>
            <div class="ffb-help-flow-title">{{ __('filament-form-builder::form.inbound_help.'.$title) }}</div>
            <div class="ffb-help-flow-sub">{{ __('filament-form-builder::form.inbound_help.'.$sub) }}</div>
        </div>
        @if (! $loop->last)
            <svg class="ffb-help-flow-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M4 10h12m-4-4 4 4-4 4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        @endif
    @endforeach
</div>
