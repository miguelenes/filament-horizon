<?php

use Eloquage\FilamentHorizon\Pages\MetricsPreview;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Filament\Support\Enums\Width;
use Livewire\Livewire;

beforeEach(function () {
    $this->api = Mockery::mock(HorizonApi::class);
    app()->instance(HorizonApi::class, $this->api);
    config()->set('app.env', 'local');
});

it('can render metrics preview page for jobs', function () {
    $this->api->shouldReceive('getJobSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([
        ['name' => 'App\Jobs\TestJob', 'throughput' => 100, 'runtime' => 50],
    ]);

    Livewire::test(MetricsPreview::class, ['type' => 'jobs', 'metricSlug' => 'App\Jobs\TestJob'])
        ->assertSuccessful();
});

it('can render metrics preview page for queues', function () {
    $this->api->shouldReceive('getQueueSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredQueues')->andReturn([
        ['name' => 'default', 'throughput' => 200, 'runtime' => 60],
    ]);

    Livewire::test(MetricsPreview::class, ['type' => 'queues', 'metricSlug' => 'default'])
        ->assertSuccessful();
});

it('has correct title for job', function () {
    $this->api->shouldReceive('getJobSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([
        ['name' => 'App\Jobs\TestJob', 'throughput' => 100, 'runtime' => 50],
    ]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'jobs', 'metricSlug' => 'App\Jobs\TestJob']);

    expect($page->call('getTitle'))->toBe('TestJob');
});

it('has correct title for queue', function () {
    $this->api->shouldReceive('getQueueSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredQueues')->andReturn([
        ['name' => 'default', 'throughput' => 200, 'runtime' => 60],
    ]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'queues', 'metricSlug' => 'default']);

    expect($page->call('getTitle'))->toBe('default');
});

it('mounts with type and metric slug', function () {
    $this->api->shouldReceive('getJobSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    Livewire::test(MetricsPreview::class, ['type' => 'jobs', 'metricSlug' => 'App%5CJobs%5CTestJob'])
        ->assertSet('type', 'jobs')
        ->assertSet('metricSlug', 'App\Jobs\TestJob');
});

it('gets job snapshots', function () {
    $snapshots = [
        (object) ['time' => 1000, 'throughput' => 10, 'runtime' => 50],
    ];

    $this->api->shouldReceive('getJobSnapshots')->with('App\Jobs\TestJob')->andReturn($snapshots);
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([
        ['name' => 'App\Jobs\TestJob', 'throughput' => 100, 'runtime' => 50],
    ]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'jobs', 'metricSlug' => 'App\Jobs\TestJob']);

    expect($page->call('getSnapshots'))->toBe($snapshots);
});

it('gets queue snapshots', function () {
    $snapshots = [
        (object) ['time' => 1000, 'throughput' => 20, 'runtime' => 60],
    ];

    $this->api->shouldReceive('getQueueSnapshots')->with('default')->andReturn($snapshots);
    $this->api->shouldReceive('getMeasuredQueues')->andReturn([
        ['name' => 'default', 'throughput' => 200, 'runtime' => 60],
    ]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'queues', 'metricSlug' => 'default']);

    expect($page->call('getSnapshots'))->toBe($snapshots);
});

it('gets metric info for job', function () {
    $this->api->shouldReceive('getJobSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([
        ['name' => 'App\Jobs\TestJob', 'throughput' => 100, 'runtime' => 50],
    ]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'jobs', 'metricSlug' => 'App\Jobs\TestJob']);

    $info = $page->call('getMetricInfo');
    expect($info)->toBeArray();
    expect($info['name'])->toBe('App\Jobs\TestJob');
    expect($info['throughput'])->toBe(100);
});

it('gets metric info for queue', function () {
    $this->api->shouldReceive('getQueueSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredQueues')->andReturn([
        ['name' => 'default', 'throughput' => 200, 'runtime' => 60],
    ]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'queues', 'metricSlug' => 'default']);

    $info = $page->call('getMetricInfo');
    expect($info)->toBeArray();
    expect($info['name'])->toBe('default');
    expect($info['throughput'])->toBe(200);
});

it('returns default metric info when not found', function () {
    $this->api->shouldReceive('getJobSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'jobs', 'metricSlug' => 'Unknown']);

    $info = $page->call('getMetricInfo');
    expect($info)->toBeArray();
    expect($info['name'])->toBe('Unknown');
    expect($info['throughput'])->toBe(0);
    expect($info['runtime'])->toBe(0);
});

it('generates chart data correctly', function () {
    $snapshots = [
        (object) ['time' => 1000, 'throughput' => 10, 'runtime' => 5000],
        (object) ['time' => 2000, 'throughput' => 20, 'runtime' => 6000],
    ];

    $this->api->shouldReceive('getJobSnapshots')->andReturn($snapshots);
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([
        ['name' => 'App\Jobs\TestJob', 'throughput' => 100, 'runtime' => 50],
    ]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'jobs', 'metricSlug' => 'App\Jobs\TestJob']);

    $chartData = $page->call('getChartData');
    expect($chartData)->toBeArray();
    expect($chartData)->toHaveKeys(['labels', 'throughput', 'runtime']);
    expect($chartData['throughput'])->toBe([10, 20]);
    expect($chartData['runtime'])->toBe([5.0, 6.0]);
});

it('extracts job base name correctly', function () {
    $this->api->shouldReceive('getJobSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'jobs', 'metricSlug' => 'App\Jobs\TestJob']);

    $instance = $page->instance();
    $reflection = new ReflectionClass(MetricsPreview::class);
    $method = $reflection->getMethod('getJobBaseName');
    $method->setAccessible(true);

    expect($method->invoke($instance, 'App\Jobs\TestJob'))->toBe('TestJob');
});

it('formats runtime correctly', function () {
    $this->api->shouldReceive('getJobSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'jobs', 'metricSlug' => 'App\Jobs\TestJob']);

    $instance = $page->instance();
    $reflection = new ReflectionClass(MetricsPreview::class);
    $method = $reflection->getMethod('formatRuntime');
    $method->setAccessible(true);

    expect($method->invoke($instance, 50.5))->toBe('50.50ms');
});

it('has full width content', function () {
    $this->api->shouldReceive('getJobSnapshots')->andReturn([]);
    $this->api->shouldReceive('getMeasuredJobs')->andReturn([]);

    $page = Livewire::test(MetricsPreview::class, ['type' => 'jobs', 'metricSlug' => 'App\Jobs\TestJob']);

    expect($page->call('getMaxContentWidth'))->toBe(Width::Full);
});

it('can access in local environment', function () {
    config()->set('app.env', 'local');

    expect(MetricsPreview::canAccess())->toBeTrue();
});

it('checks authorization gate in production', function () {
    config()->set('app.env', 'production');

    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(MetricsPreview::canAccess())->toBeTrue();

    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(MetricsPreview::canAccess())->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
