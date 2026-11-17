@php($t = fn (string $key, array $replace = []) => __('filament-form-builder::form.results.'.$key, $replace))
@php($statistics = $this->getStatistics())
@php($total = $statistics[0]->total ?? 0)
<x-filament-panels::page>
    <div class="ffb-results">
        <x-filament::section>
            <div class="ffb-results-filters">
                <label class="ffb-results-filter">
                    <span>{{ __('filament-form-builder::form.filters.from') }}</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="from" />
                    </x-filament::input.wrapper>
                </label>
                <label class="ffb-results-filter">
                    <span>{{ __('filament-form-builder::form.filters.until') }}</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="until" />
                    </x-filament::input.wrapper>
                </label>
                <p class="ffb-results-total">{{ $t('submission_count', ['count' => $total]) }}</p>
            </div>
        </x-filament::section>

        @if ($total === 0)
            <x-filament::section>
                <p class="ffb-results-empty">{{ $t('empty') }}</p>
            </x-filament::section>
        @else
            @foreach ($statistics as $field)
                <x-filament::section :heading="$field->label" :description="$t('answered', ['answered' => $field->answered, 'total' => $field->total])">
                    @if ($field->nps !== null)
                        <div class="ffb-results-nps">
                            <div class="ffb-results-nps-score">
                                <span class="ffb-results-nps-label">NPS</span>
                                <span class="ffb-results-nps-value">{{ $field->nps['score'] }}</span>
                            </div>
                            <div class="ffb-results-nps-parts">
                                <span class="ffb-results-pill ffb-results-pill--good">{{ $t('promoters') }}: {{ $field->nps['promoters'] }}</span>
                                <span class="ffb-results-pill">{{ $t('passives') }}: {{ $field->nps['passives'] }}</span>
                                <span class="ffb-results-pill ffb-results-pill--bad">{{ $t('detractors') }}: {{ $field->nps['detractors'] }}</span>
                            </div>
                        </div>
                    @endif

                    @if ($field->average !== null)
                        <dl class="ffb-results-numbers">
                            <div><dt>{{ $t('average') }}</dt><dd>{{ number_format($field->average, 2, ',', ' ') }}</dd></div>
                            <div><dt>{{ $t('min') }}</dt><dd>{{ $field->min }}</dd></div>
                            <div><dt>{{ $t('max') }}</dt><dd>{{ $field->max }}</dd></div>
                        </dl>
                    @endif

                    @if ($field->isChartable && $field->distribution !== [])
                        @php($peak = max(1, ...array_values($field->distribution)))
                        <div class="ffb-results-bars" role="list">
                            @foreach ($field->distribution as $answer => $count)
                                <div class="ffb-results-bar" role="listitem">
                                    <span class="ffb-results-bar-label">{{ $answer }}</span>
                                    <span class="ffb-results-bar-track">
                                        <span class="ffb-results-bar-fill" style="width: {{ round($count / $peak * 100, 1) }}%"></span>
                                    </span>
                                    <span class="ffb-results-bar-count">
                                        {{ $count }}
                                        @if ($field->answered > 0)
                                            <span class="ffb-results-bar-share">({{ round($count / $field->answered * 100) }}%)</span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-filament::section>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
