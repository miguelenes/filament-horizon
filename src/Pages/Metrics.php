<?php

namespace Miguelenes\FilamentHorizon\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Url;
use Miguelenes\FilamentHorizon\Clusters\Horizon;
use Miguelenes\FilamentHorizon\Concerns\AuthorizesHorizonAccess;
use Miguelenes\FilamentHorizon\Services\HorizonApi;

class Metrics extends Page
{
    use AuthorizesHorizonAccess;

    protected string $view = 'filament-horizon::pages.metrics';

    protected static ?string $cluster = Horizon::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 6;

    #[Url]
    public string $type = 'jobs';

    public static function getNavigationLabel(): string
    {
        return __('filament-horizon::horizon.pages.metrics.navigation_label');
    }

    public function getTitle(): string
    {
        return __('filament-horizon::horizon.pages.metrics.title');
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getMetrics(): array
    {
        $api = app(HorizonApi::class);

        if ($this->type === 'queues') {
            return $api->getMeasuredQueues();
        }

        return $api->getMeasuredJobs();
    }

    protected function getJobBaseName(string $name): string
    {
        $parts = explode('\\', $name);

        return end($parts);
    }

    protected function formatRuntime(float $runtime): string
    {
        return number_format($runtime, 2) . 'ms';
    }

    public function getMaxContentWidth(): Width | null | string
    {
        return Width::Full;
    }
}
