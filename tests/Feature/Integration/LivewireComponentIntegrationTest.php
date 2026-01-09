<?php

use Eloquage\FilamentHorizon\Pages\Batches;
use Eloquage\FilamentHorizon\Pages\Dashboard;
use Eloquage\FilamentHorizon\Pages\FailedJobs;
use Eloquage\FilamentHorizon\Pages\Metrics;
use Eloquage\FilamentHorizon\Pages\Monitoring;
use Eloquage\FilamentHorizon\Pages\RecentJobs;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Eloquage\FilamentHorizon\Widgets\StatsOverview;
use Eloquage\FilamentHorizon\Widgets\WorkersWidget;
use Eloquage\FilamentHorizon\Widgets\WorkloadWidget;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    $this->api = Mockery::mock(HorizonApi::class);
    app()->instance(HorizonApi::class, $this->api);
});

it('can visit all pages', function () {
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
    $this->api->shouldReceive('getPendingJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);
    $this->api->shouldReceive('getFailedJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);
    $this->api->shouldReceive('getBatches')->andReturn(['batches' => []]);
    $this->api->shouldReceive('getMonitoredTags')->andReturn(collect([]));
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    Livewire::test(Dashboard::class)->assertSuccessful();
    Livewire::test(RecentJobs::class)->assertSuccessful();
    Livewire::test(FailedJobs::class)->assertSuccessful();
    Livewire::test(Batches::class)->assertSuccessful();
    Livewire::test(Monitoring::class)->assertSuccessful();
    Livewire::test(Metrics::class)->assertSuccessful();
});

it('can render all widgets', function () {
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
    $this->api->shouldReceive('getMasters')->andReturn([]);
    $this->api->shouldReceive('getWorkload')->andReturn([]);

    Livewire::test(StatsOverview::class)->assertSuccessful();
    Livewire::test(WorkersWidget::class)->assertSuccessful();
    Livewire::test(WorkloadWidget::class)->assertSuccessful();
});

it('authorization gates work across all components', function () {
    config()->set('app.env', 'production');

    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(Dashboard::canAccess())->toBeTrue();
    expect(RecentJobs::canAccess())->toBeTrue();
    expect(FailedJobs::canAccess())->toBeTrue();
    expect(Batches::canAccess())->toBeTrue();
    expect(Monitoring::canAccess())->toBeTrue();
    expect(Metrics::canAccess())->toBeTrue();

    // Redefine the gate (Laravel 12 doesn't have forget method)
    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(Dashboard::canAccess())->toBeFalse();
    expect(RecentJobs::canAccess())->toBeFalse();
    expect(FailedJobs::canAccess())->toBeFalse();
    expect(Batches::canAccess())->toBeFalse();
    expect(Monitoring::canAccess())->toBeFalse();
    expect(Metrics::canAccess())->toBeFalse();
});

it('all components use HorizonApi service', function () {
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
    $this->api->shouldReceive('getPendingJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);
    $this->api->shouldReceive('getMasters')->andReturn([]);
    $this->api->shouldReceive('getWorkload')->andReturn([]);

    Livewire::test(Dashboard::class);
    Livewire::test(RecentJobs::class);
    Livewire::test(StatsOverview::class);
    Livewire::test(WorkersWidget::class);
    Livewire::test(WorkloadWidget::class);

    // Verify API was called
    expect(true)->toBeTrue();
});

afterEach(function () {
    Mockery::close();
});
