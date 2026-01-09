<?php

namespace Eloquage\FilamentHorizon\Widgets;

use Carbon\CarbonInterval;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getPollingInterval(): ?string
    {
        return '5s';
    }

    protected function getStats(): array
    {
        $api = app(HorizonApi::class);
        $stats = $api->getStats();

        $status = $stats['status'];
        $statusLabel = __('filament-horizon::horizon.status.' . $status);
        $statusColor = match ($status) {
            'running' => 'success',
            'paused' => 'warning',
            default => 'danger',
        };
        $statusIcon = match ($status) {
            'running' => 'heroicon-o-check-circle',
            'paused' => 'heroicon-o-pause-circle',
            default => 'heroicon-o-exclamation-circle',
        };

        $recentPeriod = $this->humanizePeriod($stats['periods']['recentJobs'] ?? 60);
        $failedPeriod = $this->humanizePeriod($stats['periods']['failedJobs'] ?? 10080);

        $maxWait = '-';
        $maxWaitQueue = null;
        if ($stats['wait']->isNotEmpty()) {
            $waitData = $stats['wait']->first();
            $maxWait = $this->humanizeTime($waitData);
            $maxWaitQueue = $stats['wait']->keys()->first();
            if ($maxWaitQueue) {
                $maxWaitQueue = explode(':', $maxWaitQueue)[1] ?? $maxWaitQueue;
            }
        }

        return [
            Stat::make(__('filament-horizon::horizon.widgets.stats.jobs_per_minute'), number_format($stats['jobsPerMinute'] ?? 0))
                ->icon('heroicon-o-bolt'),

            Stat::make(__('filament-horizon::horizon.widgets.stats.jobs_past_hour', ['period' => $recentPeriod]), number_format($stats['recentJobs'] ?? 0))
                ->icon('heroicon-o-clock'),

            Stat::make(__('filament-horizon::horizon.widgets.stats.failed_jobs_past', ['period' => $failedPeriod]), number_format($stats['failedJobs'] ?? 0))
                ->icon('heroicon-o-x-circle')
                ->color($stats['failedJobs'] > 0 ? 'danger' : 'success'),

            Stat::make(__('filament-horizon::horizon.widgets.stats.status'), $statusLabel)
                ->icon($statusIcon)
                ->color($statusColor)
                ->description($stats['pausedMasters'] > 0 ? "({$stats['pausedMasters']} paused)" : null),

            Stat::make(__('filament-horizon::horizon.widgets.stats.total_processes'), number_format($stats['processes'] ?? 0))
                ->icon('heroicon-o-cpu-chip'),

            Stat::make(__('filament-horizon::horizon.widgets.stats.max_wait_time'), $maxWait)
                ->icon('heroicon-o-clock')
                ->description($maxWaitQueue),

            Stat::make(__('filament-horizon::horizon.widgets.stats.max_runtime'), $stats['queueWithMaxRuntime'] ?? '-')
                ->icon('heroicon-o-chart-bar'),

            Stat::make(__('filament-horizon::horizon.widgets.stats.max_throughput'), $stats['queueWithMaxThroughput'] ?? '-')
                ->icon('heroicon-o-arrow-trending-up'),
        ];
    }

    protected function humanizePeriod(int $minutes): string
    {
        return CarbonInterval::minutes($minutes)->cascade()->forHumans(['short' => true]);
    }

    protected function humanizeTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        return CarbonInterval::seconds($seconds)->cascade()->forHumans(['short' => true]);
    }
}
