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


afterEach(function () {
    Mockery::close();
});
