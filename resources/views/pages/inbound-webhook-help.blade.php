@php($t = fn (string $key) => __('filament-form-builder::form.inbound_help.'.$key))
@php($links = $this->getSetupLinks())
<x-filament-panels::page>
    <div class="ffb-help">
        <x-filament::section :heading="$t('intro_heading')">
            <p class="ffb-help-text">{{ $t('intro_body') }}</p>
            <div>
                @include('filament-form-builder::pages.partials.inbound-flow')
            </div>
        </x-filament::section>

        <x-filament::section :heading="$t('setup_heading')">
            <div class="ffb-help-steps">
                @foreach ([['step1', '01-enable', 'screenshot_enable'], ['step2', null, null], ['step3', '02-mapping', 'screenshot_mapping']] as [$step, $image, $caption])
                    <div>
                        <h3 class="ffb-help-h3">{{ $t($step.'_title') }}</h3>
                        <p class="ffb-help-text">{{ $t($step.'_body') }}</p>
                        @include('filament-form-builder::pages.partials.inbound-links', ['items' => $links[$step]])
                        @if ($image)
                            <figure class="ffb-help-figure">
                                <img src="{{ \Madbox99\FilamentFormBuilder\Filament\Pages\InboundWebhookHelp::image($image) }}"
                                     alt="{{ $t($caption) }}" loading="lazy">
                                <figcaption>{{ $t($caption) }}</figcaption>
                            </figure>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section :heading="$t('request_heading')">
            <p class="ffb-help-text">{{ $t('request_body') }}</p>
            <h4 class="ffb-help-h4">{{ $t('request_json') }}</h4>
            <pre class="ffb-help-code"><code>{{ $this->getExamplePayloadJson() }}</code></pre>
            <h4 class="ffb-help-h4">{{ $t('request_curl') }}</h4>
            <pre class="ffb-help-code"><code>{{ $this->getCurlExample() }}</code></pre>

            <h4 class="ffb-help-h4">{{ $t('codes_heading') }}</h4>
            <div class="ffb-help-table-wrap">
                <table class="ffb-help-table">
                    <thead>
                        <tr>
                            <th>{{ $t('codes_code') }}</th>
                            <th>{{ $t('codes_meaning') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->getResponseCodes() as $code => $meaning)
                            <tr>
                                <td>{{ $code }}</td>
                                <td>{{ $meaning }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section :heading="$t('tools_heading')">
            <div class="ffb-help-tools">
                @foreach (\Madbox99\FilamentFormBuilder\Filament\Pages\InboundWebhookHelp::toolGuides() as $guide)
                    <div class="ffb-help-tool">
                        <div class="ffb-help-tool-head">
                            <h3 class="ffb-help-h3">{{ $guide['name'] }}</h3>
                            <x-filament::link :href="$guide['url']" target="_blank" rel="noopener noreferrer" size="sm"
                                              icon="heroicon-m-arrow-top-right-on-square" icon-position="after">
                                {{ $t('tools_docs') }}
                            </x-filament::link>
                        </div>
                        <ol class="ffb-help-list">
                            @foreach ($guide['steps'] as $stepText)
                                <li>{{ $stepText }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section :heading="$t('security_heading')">
            <ul class="ffb-help-list">
                @foreach (__('filament-form-builder::form.inbound_help.security_items') as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </x-filament::section>

        <x-filament::section :heading="$t('troubleshooting_heading')">
            @include('filament-form-builder::pages.partials.inbound-links', ['items' => $links['troubleshooting']])
            <ul class="ffb-help-list">
                @foreach (__('filament-form-builder::form.inbound_help.troubleshooting_items') as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            <figure class="ffb-help-figure">
                <img src="{{ \Madbox99\FilamentFormBuilder\Filament\Pages\InboundWebhookHelp::image('03-submissions') }}"
                     alt="{{ $t('screenshot_submissions') }}" loading="lazy">
                <figcaption>{{ $t('screenshot_submissions') }}</figcaption>
            </figure>
        </x-filament::section>
    </div>
</x-filament-panels::page>
