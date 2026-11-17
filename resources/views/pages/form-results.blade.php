@php
    use Madbox99\FilamentFormBuilder\Filament\Widgets\ResultChart;
    use Madbox99\FilamentFormBuilder\Support\ResultCharts;

    $t = fn (string $key, array $replace = []) => __('filament-form-builder::form.results.'.$key, $replace);
    $statistics = $this->getStatistics();
    $total = $this->getSubmissionTotal();
    $highlights = $this->getHighlights($statistics);
    $timeline = $this->getTimeline();
    $range = $this->activeRange();
    $filterKey = md5(($this->from ?? '').'|'.($this->until ?? '').'|'.$total);
@endphp
<x-filament-panels::page>
    <div class="ffb-results">
        <div class="ffb-results-toolbar">
            <div class="ffb-results-presets" role="group">
                @foreach (['7' => $t('range_7'), '30' => $t('range_30'), '90' => $t('range_90'), 'all' => $t('range_all')] as $key => $label)
                    @php($key = (string) $key)
                    <x-filament::button
                        size="sm"
                        :color="$range === $key ? 'primary' : 'gray'"
                        :outlined="$range !== $key"
                        wire:click="setRange('{{ $key }}')"
                    >
                        {{ $label }}
                    </x-filament::button>
                @endforeach
            </div>
            <div class="ffb-results-dates">
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
            </div>
        </div>

        <div class="ffb-results-kpis">
            <div class="ffb-kpi">
                <span class="ffb-kpi-label">{{ $t('kpi_submissions') }}</span>
                <span class="ffb-kpi-value">{{ number_format($total, 0, ',', ' ') }}</span>
            </div>
            @if ($highlights['nps'] !== null)
                @php($score = $highlights['nps']->nps['score'])
                <div class="ffb-kpi">
                    <span class="ffb-kpi-label">NPS</span>
                    <span class="ffb-kpi-value {{ $score >= 30 ? 'ffb-kpi-good' : ($score < 0 ? 'ffb-kpi-bad' : 'ffb-kpi-mid') }}">{{ $score > 0 ? '+'.$score : $score }}</span>
                    <span class="ffb-kpi-hint">{{ $highlights['nps']->label }}</span>
                </div>
            @endif
            @if ($highlights['rating'] !== null)
                <div class="ffb-kpi">
                    <span class="ffb-kpi-label">{{ $t('kpi_average_rating') }}</span>
                    <span class="ffb-kpi-value">{{ number_format($highlights['rating']->average, 1, ',', ' ') }}</span>
                    <span class="ffb-kpi-hint">{{ $highlights['rating']->label }}</span>
                </div>
            @endif
            @if ($highlights['completion'] !== null && $total > 0)
                <div class="ffb-kpi">
                    <span class="ffb-kpi-label">{{ $t('kpi_completion') }}</span>
                    <span class="ffb-kpi-value">{{ $highlights['completion'] }}%</span>
                    <span class="ffb-kpi-hint">{{ $t('kpi_completion_hint') }}</span>
                </div>
            @endif
        </div>

        @if ($total === 0)
            <x-filament::section>
                <div class="ffb-results-empty-state">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="ffb-results-empty-icon" />
                    <p class="ffb-results-empty">{{ $t('empty') }}</p>
                </div>
            </x-filament::section>
        @else
            @if (count($timeline) > 1)
                @livewire(ResultChart::class, [
                    'chart' => ResultCharts::timeline($timeline),
                    'chartHeading' => $t('timeline'),
                    'height' => '220px',
                ], key('ffb-timeline-'.$filterKey))
            @endif

            <div class="ffb-results-grid">
                @foreach ($statistics as $field)
                    <div @class(['ffb-results-card', 'ffb-results-card--wide' => $field->nps !== null])>
                        <x-filament::section :heading="$field->label" :description="$t('answered', ['answered' => $field->answered, 'total' => $field->total])">
                            @if ($field->average !== null)
                                <dl class="ffb-results-numbers">
                                    <div><dt>{{ $t('average') }}</dt><dd>{{ number_format($field->average, 2, ',', ' ') }}</dd></div>
                                    <div><dt>{{ $t('min') }}</dt><dd>{{ $field->min }}</dd></div>
                                    <div><dt>{{ $t('max') }}</dt><dd>{{ $field->max }}</dd></div>
                                </dl>
                            @endif

                            @if ($field->nps !== null)
                                <div class="ffb-results-nps-layout">
                                    <div class="ffb-results-nps-gauge">
                                        @livewire(ResultChart::class, [
                                            'chart' => ResultCharts::npsSplit($field->nps),
                                            'height' => '220px',
                                        ], key('ffb-nps-'.$field->key.'-'.$filterKey))
                                        <div class="ffb-results-nps-center">
                                            <span class="ffb-results-nps-label">NPS</span>
                                            <span class="ffb-results-nps-value">{{ $field->nps['score'] > 0 ? '+'.$field->nps['score'] : $field->nps['score'] }}</span>
                                        </div>
                                    </div>
                                    <div class="ffb-results-nps-histogram">
                                        @livewire(ResultChart::class, [
                                            'chart' => ResultCharts::forField($field),
                                            'chartDescription' => $t('distribution'),
                                            'height' => '220px',
                                        ], key('ffb-field-'.$field->key.'-'.$filterKey))
                                    </div>
                                </div>
                            @elseif ($field->isChartable && $field->answered > 0)
                                @livewire(ResultChart::class, [
                                    'chart' => ResultCharts::forField($field),
                                    'height' => max(200, min(420, count($field->distribution) * 34)).'px',
                                ], key('ffb-field-'.$field->key.'-'.$filterKey))
                            @elseif (! $field->isChartable)
                                <p class="ffb-results-text-note">{{ $t('text_answers', ['count' => $field->answered]) }}</p>
                            @else
                                <p class="ffb-results-text-note">{{ $t('no_answers_yet') }}</p>
                            @endif
                        </x-filament::section>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
