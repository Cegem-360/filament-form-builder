<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Override;

/**
 * One Chart.js chart on the form results page. The page builds the whole
 * configuration (see ResultCharts) and passes it in; the widget only draws it.
 * It is not registered on any dashboard and never polls.
 */
class ResultChart extends ChartWidget
{
    /** @var array{type: string, data: array<string, mixed>, options: array<string, mixed>} */
    public array $chart = ['type' => 'bar', 'data' => ['labels' => [], 'datasets' => []], 'options' => []];

    public ?string $chartHeading = null;

    public ?string $chartDescription = null;

    public string $height = '260px';

    protected ?string $pollingInterval = null;

    protected static bool $isDiscovered = false;

    #[Override]
    public function getHeading(): ?string
    {
        return $this->chartHeading;
    }

    #[Override]
    public function getDescription(): ?string
    {
        return $this->chartDescription;
    }

    #[Override]
    protected function getMaxHeight(): ?string
    {
        return $this->height;
    }

    #[Override]
    protected function getType(): string
    {
        return $this->chart['type'];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function getData(): array
    {
        return $this->chart['data'];
    }

    /**
     * @return array<string, mixed>|RawJs|null
     */
    #[Override]
    protected function getOptions(): array|RawJs|null
    {
        return [
            'maintainAspectRatio' => false,
            ...$this->chart['options'],
        ];
    }
}
