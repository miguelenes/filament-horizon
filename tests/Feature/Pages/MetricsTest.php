<?php

use Eloquage\FilamentHorizon\Pages\Metrics;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Filament\Support\Enums\Width;
use Livewire\Livewire;

beforeEach(function () {
    $this->api = Mockery::mock(HorizonApi::class);
    app()->instance(HorizonApi::class, $this->api);
});

it('can render metrics page', function () {
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    Livewire::test(Metrics::class)
        ->assertSuccessful();
});

it('has correct navigation label', function () {
    expect(Metrics::getNavigationLabel())->toBeString();
});

it('has correct title', function () {
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    $page = Livewire::test(Metrics::class);

    expect($page->call('getTitle'))->toBeString();
});

it('gets measured jobs by default', function () {
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    Livewire::test(Metrics::class)
        ->assertSet('type', 'jobs')
        ->call('getMetrics');
});

it('gets measured queues when type is queues', function () {
    $this->api->shouldReceive('getMeasuredQueues')->andReturn([]);

    Livewire::test(Metrics::class)
        ->set('type', 'queues')
        ->call('getMetrics');
});

it('can change type', function () {
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);
    $this->api->shouldReceive('getMeasuredQueues')->andReturn([]);

    Livewire::test(Metrics::class)
        ->call('setType', 'queues')
        ->assertSet('type', 'queues');
});

it('extracts job base name correctly', function () {
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    $page = Livewire::test(Metrics::class);

    $reflection = new ReflectionClass(Metrics::class);
    $instance = $page->instance();
    $method = $reflection->getMethod('getJobBaseName');
    $method->setAccessible(true);

    expect($method->invoke($instance, 'App\Jobs\TestJob'))->toBe('TestJob');
});

it('formats runtime correctly', function () {
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    $page = Livewire::test(Metrics::class);

    $reflection = new ReflectionClass(Metrics::class);
    $instance = $page->instance();
    $method = $reflection->getMethod('formatRuntime');
    $method->setAccessible(true);

    expect($method->invoke($instance, 50.5))->toBe('50.50ms');
});

it('has full width content', function () {
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    $page = Livewire::test(Metrics::class);

    expect($page->call('getMaxContentWidth'))->toBe(Width::Full);
});

it('can access in local environment', function () {
    config()->set('app.env', 'local');

    expect(Metrics::canAccess())->toBeTrue();
});

it('checks authorization gate in production', function () {
    config()->set('app.env', 'production');

    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(Metrics::canAccess())->toBeTrue();

    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(Metrics::canAccess())->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
