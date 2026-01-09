<?php

use Eloquage\FilamentHorizon\Services\HorizonApi;
use Eloquage\FilamentHorizon\Widgets\StatsOverview;

beforeEach(function () {
    $this->api = Mockery::mock(HorizonApi::class);
    app()->instance(HorizonApi::class, $this->api);
});

it('can get stats', function () {
    $this->api->shouldReceive('getStats')->andReturn([
        'jobsPerMinute' => 50,
        'recentJobs' => 100,
        'failedJobs' => 5,
        'status' => 'running',
        'pausedMasters' => 0,
        'processes' => 3,
        'wait' => collect(['default:queue' => 10]),
        'queueWithMaxRuntime' => 'default',
        'queueWithMaxThroughput' => 'high',
        'periods' => [
            'recentJobs' => 60,
            'failedJobs' => 10080,
        ],
    ]);

    $widget = new StatsOverview;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect($stats)->toHaveCount(8);
});

it('displays correct stats', function () {
    $this->api->shouldReceive('getStats')->andReturn([
        'jobsPerMinute' => 50,
        'recentJobs' => 100,
        'failedJobs' => 5,
        'status' => 'running',
        'pausedMasters' => 0,
        'processes' => 3,
        'wait' => collect(['default:queue' => 10]),
        'queueWithMaxRuntime' => 'default',
        'queueWithMaxThroughput' => 'high',
        'periods' => [
            'recentJobs' => 60,
            'failedJobs' => 10080,
        ],
    ]);

    $widget = new StatsOverview;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect($stats)->toHaveCount(8);
});

it('shows correct status color for running', function () {
    $this->api->shouldReceive('getStats')->andReturn([
        'jobsPerMinute' => 50,
        'recentJobs' => 100,
        'failedJobs' => 5,
        'status' => 'running',
        'pausedMasters' => 0,
        'processes' => 3,
        'wait' => collect([]),
        'queueWithMaxRuntime' => 'default',
        'queueWithMaxThroughput' => 'high',
        'periods' => [
            'recentJobs' => 60,
            'failedJobs' => 10080,
        ],
    ]);

    $widget = new StatsOverview;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    $statusStat = collect($stats)->first(fn ($stat) => $stat->getLabel() === __('filament-horizon::horizon.widgets.stats.status'));
    expect($statusStat->getColor())->toBe('success');
});

it('shows correct status color for paused', function () {
    $this->api->shouldReceive('getStats')->andReturn([
        'jobsPerMinute' => 50,
        'recentJobs' => 100,
        'failedJobs' => 5,
        'status' => 'paused',
        'pausedMasters' => 1,
        'processes' => 3,
        'wait' => collect([]),
        'queueWithMaxRuntime' => 'default',
        'queueWithMaxThroughput' => 'high',
        'periods' => [
            'recentJobs' => 60,
            'failedJobs' => 10080,
        ],
    ]);

    $widget = new StatsOverview;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    $statusStat = collect($stats)->first(fn ($stat) => $stat->getLabel() === __('filament-horizon::horizon.widgets.stats.status'));
    expect($statusStat->getColor())->toBe('warning');
});

it('shows correct status color for inactive', function () {
    $this->api->shouldReceive('getStats')->andReturn([
        'jobsPerMinute' => 0,
        'recentJobs' => 0,
        'failedJobs' => 0,
        'status' => 'inactive',
        'pausedMasters' => 0,
        'processes' => 0,
        'wait' => collect([]),
        'queueWithMaxRuntime' => null,
        'queueWithMaxThroughput' => null,
        'periods' => [
            'recentJobs' => 60,
            'failedJobs' => 10080,
        ],
    ]);

    $widget = new StatsOverview;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    $statusStat = collect($stats)->first(fn ($stat) => $stat->getLabel() === __('filament-horizon::horizon.widgets.stats.status'));
    expect($statusStat->getColor())->toBe('danger');
});

it('shows failed jobs color correctly', function () {
    $this->api->shouldReceive('getStats')->andReturn([
        'jobsPerMinute' => 50,
        'recentJobs' => 100,
        'failedJobs' => 5,
        'status' => 'running',
        'pausedMasters' => 0,
        'processes' => 3,
        'wait' => collect([]),
        'queueWithMaxRuntime' => 'default',
        'queueWithMaxThroughput' => 'high',
        'periods' => [
            'recentJobs' => 60,
            'failedJobs' => 10080,
        ],
    ]);

    $widget = new StatsOverview;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    $failedStat = collect($stats)->first(fn ($stat) => str_contains($stat->getLabel(), 'Failed'));
    expect($failedStat->getColor())->toBe('danger');
});

it('shows success color when no failed jobs', function () {
    $this->api->shouldReceive('getStats')->andReturn([
        'jobsPerMinute' => 50,
        'recentJobs' => 100,
        'failedJobs' => 0,
        'status' => 'running',
        'pausedMasters' => 0,
        'processes' => 3,
        'wait' => collect([]),
        'queueWithMaxRuntime' => 'default',
        'queueWithMaxThroughput' => 'high',
        'periods' => [
            'recentJobs' => 60,
            'failedJobs' => 10080,
        ],
    ]);

    $widget = new StatsOverview;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    $failedStat = collect($stats)->first(fn ($stat) => str_contains($stat->getLabel(), 'Failed'));
    expect($failedStat->getColor())->toBe('success');
});

it('humanizes period correctly', function () {
    $widget = new StatsOverview;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('humanizePeriod');
    $method->setAccessible(true);

    expect($method->invoke($widget, 60))->toBeString();
    expect($method->invoke($widget, 1440))->toBeString();
});

it('humanizes time correctly', function () {
    $widget = new StatsOverview;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('humanizeTime');
    $method->setAccessible(true);

    expect($method->invoke($widget, 30))->toBe('30s');
    expect($method->invoke($widget, 120))->toBeString();
});

it('has correct polling interval', function () {
    $widget = new StatsOverview;
    $reflection = new ReflectionClass(StatsOverview::class);
    $method = $reflection->getMethod('getPollingInterval');
    $method->setAccessible(true);
    expect($method->invoke($widget))->toBe('5s');
});

it('has full column span', function () {
    $widget = new StatsOverview;
    expect($widget->getColumnSpan())->toBe('full');
});

it('handles empty wait data', function () {
    $this->api->shouldReceive('getStats')->andReturn([
        'jobsPerMinute' => 50,
        'recentJobs' => 100,
        'failedJobs' => 5,
        'status' => 'running',
        'pausedMasters' => 0,
        'processes' => 3,
        'wait' => collect([]),
        'queueWithMaxRuntime' => 'default',
        'queueWithMaxThroughput' => 'high',
        'periods' => [
            'recentJobs' => 60,
            'failedJobs' => 10080,
        ],
    ]);

    $widget = new StatsOverview;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    $waitStat = collect($stats)->first(fn ($stat) => str_contains($stat->getLabel(), 'Max Wait'));
    expect($waitStat->getValue())->toBe('-');
});

afterEach(function () {
    Mockery::close();
});
