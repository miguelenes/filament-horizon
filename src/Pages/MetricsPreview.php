<?php

namespace Miguelenes\FilamentHorizon\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Miguelenes\FilamentHorizon\Clusters\Horizon;
use Miguelenes\FilamentHorizon\Concerns\AuthorizesHorizonAccess;
use Miguelenes\FilamentHorizon\Services\HorizonApi;

class MetricsPreview extends Page
{
    use AuthorizesHorizonAccess;

    protected static ?string $slug = 'metrics-preview';

    protected string $view = 'filament-horizon::pages.metrics-preview';

    protected static ?string $cluster = Horizon::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static bool $shouldRegisterNavigation = false;

    public string $type = 'jobs';

    public string $metricSlug = '';

    public static function getRoutePath(Panel $panel): string
    {
        return '/metrics-preview/{type}/{metricSlug}';
    }

    public function mount(string $type = 'jobs', string $metricSlug = ''): void
    {
        $this->type = $type;
        $this->metricSlug = urldecode($metricSlug);
    }

    public function getTitle(): string
    {
        return $this->type === 'jobs'
            ? $this->getJobBaseName($this->metricSlug)
            : $this->metricSlug;
    }

    public function getSnapshots(): array
    {
        $api = app(HorizonApi::class);

        if ($this->type === 'queues') {
            return $api->getQueueSnapshots($this->metricSlug);
        }

        return $api->getJobSnapshots($this->metricSlug);
    }

    public function getMetricInfo(): array
    {
        $api = app(HorizonApi::class);

        if ($this->type === 'queues') {
            $queues = $api->getMeasuredQueues();
            foreach ($queues as $queue) {
                if ($queue['name'] === $this->metricSlug) {
                    return $queue;
                }
            }
        } else {
            $jobs = $api->getMeasuredJobs();
            foreach ($jobs as $job) {
                if ($job['name'] === $this->metricSlug) {
                    return $job;
                }
            }
        }

        return [
            'name' => $this->metricSlug,
            'throughput' => 0,
            'runtime' => 0,
        ];
    }

    public function getChartData(): array
    {
        $snapshots = $this->getSnapshots();

        $labels = [];
        $throughputData = [];
        $runtimeData = [];

        foreach ($snapshots as $snapshot) {
            $time = isset($snapshot->time) ? Carbon::createFromTimestamp($snapshot->time)->format('H:i') : '';
            $labels[] = $time;
            $throughputData[] = $snapshot->throughput ?? 0;
            $runtimeData[] = round(($snapshot->runtime ?? 0) / 1000, 2);
        }

        return [
            'labels' => $labels,
            'throughput' => $throughputData,
            'runtime' => $runtimeData,
        ];
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
