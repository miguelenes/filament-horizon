<?php

use Eloquage\FilamentHorizon\Pages\BatchPreview;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

beforeEach(function () {
    $this->api = Mockery::mock(HorizonApi::class);
    app()->instance(HorizonApi::class, $this->api);
    config()->set('app.env', 'local');
});

it('can render batch preview page', function () {
    $this->api->shouldReceive('getBatch')->andReturn([
        'batch' => (object) ['id' => '1', 'totalJobs' => 10, 'pendingJobs' => 0],
        'failedJobs' => collect([]),
    ]);

    Livewire::test(BatchPreview::class, ['batchId' => '1'])
        ->assertSuccessful();
});

it('has correct title', function () {
    $this->api->shouldReceive('getBatch')->andReturn([
        'batch' => (object) ['id' => '1'],
        'failedJobs' => collect([]),
    ]);

    $page = Livewire::test(BatchPreview::class, ['batchId' => '1']);

    expect($page->call('getTitle'))->toBe('Batch Details');
});

it('mounts with batch id', function () {
    $this->api->shouldReceive('getBatch')->andReturn([
        'batch' => (object) ['id' => '1'],
        'failedJobs' => collect([]),
    ]);

    Livewire::test(BatchPreview::class, ['batchId' => 'batch-123'])
        ->assertSet('batchId', 'batch-123');
});

it('gets batch from api', function () {
    $batch = [
        'batch' => (object) ['id' => '1', 'totalJobs' => 10],
        'failedJobs' => collect([]),
    ];

    $this->api->shouldReceive('getBatch')->with('1')->andReturn($batch);

    $page = Livewire::test(BatchPreview::class, ['batchId' => '1']);

    expect($page->call('getBatch'))->toBe($batch);
});

it('handles missing batch gracefully', function () {
    $this->api->shouldReceive('getBatch')->with('1')->andReturn([
        'batch' => null,
        'failedJobs' => null,
    ]);

    $page = Livewire::test(BatchPreview::class, ['batchId' => '1']);

    $result = $page->call('getBatch');
    expect($result['batch'])->toBeNull();
});

it('can retry batch', function () {
    Bus::fake();

    $this->api->shouldReceive('getBatch')->andReturn([
        'batch' => (object) ['id' => '1', 'failedJobIds' => ['1', '2']],
        'failedJobs' => collect([]),
    ]);
    $this->api->shouldReceive('retryBatch')->with('1')->once();

    Livewire::test(BatchPreview::class, ['batchId' => '1'])
        ->call('retryBatch')
        ->assertSet('isRetrying', true)
        ->assertNotified(Notification::make()
            ->title(__('filament-horizon::horizon.messages.batch_retried'))
            ->success());
});

it('prevents duplicate batch retries', function () {
    $this->api->shouldReceive('getBatch')->andReturn([
        'batch' => (object) ['id' => '1'],
        'failedJobs' => collect([]),
    ]);
    $this->api->shouldNotReceive('retryBatch');

    Livewire::test(BatchPreview::class, ['batchId' => '1'])
        ->set('isRetrying', true)
        ->call('retryBatch')
        ->assertSet('isRetrying', true);
});

it('formats timestamp correctly', function () {
    $this->api->shouldReceive('getBatch')->andReturn([
        'batch' => (object) ['id' => '1'],
        'failedJobs' => collect([]),
    ]);

    $page = Livewire::test(BatchPreview::class, ['batchId' => '1']);

    $instance = $page->instance();
    $reflection = new ReflectionClass(BatchPreview::class);
    $method = $reflection->getMethod('formatTimestamp');
    $method->setAccessible(true);

    $timestamp = now()->toIso8601String();
    $formatted = $method->invoke($instance, $timestamp);
    expect($formatted)->toBeString();
    expect($formatted)->toMatch('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/');

    expect($method->invoke($instance, null))->toBe('-');
});

it('calculates progress correctly', function () {
    $this->api->shouldReceive('getBatch')->andReturn([
        'batch' => (object) ['id' => '1'],
        'failedJobs' => collect([]),
    ]);

    $page = Livewire::test(BatchPreview::class, ['batchId' => '1']);

    $instance = $page->instance();
    $reflection = new ReflectionClass(BatchPreview::class);
    $method = $reflection->getMethod('calculateProgress');
    $method->setAccessible(true);

    $batch = (object) ['totalJobs' => 100, 'pendingJobs' => 50];
    expect($method->invoke($instance, $batch))->toBe(50);

    $batchComplete = (object) ['totalJobs' => 100, 'pendingJobs' => 0];
    expect($method->invoke($instance, $batchComplete))->toBe(100);

    $batchEmpty = (object) ['totalJobs' => 0, 'pendingJobs' => 0];
    expect($method->invoke($instance, $batchEmpty))->toBe(0);

    expect($method->invoke($instance, null))->toBe(0);
});

it('extracts job base name correctly', function () {
    $this->api->shouldReceive('getBatch')->andReturn([
        'batch' => (object) ['id' => '1'],
        'failedJobs' => collect([]),
    ]);

    $page = Livewire::test(BatchPreview::class, ['batchId' => '1']);

    $instance = $page->instance();
    $reflection = new ReflectionClass(BatchPreview::class);
    $method = $reflection->getMethod('getJobBaseName');
    $method->setAccessible(true);

    expect($method->invoke($instance, 'App\Jobs\TestJob'))->toBe('TestJob');
});

it('has full width content', function () {
    $this->api->shouldReceive('getBatch')->andReturn([
        'batch' => (object) ['id' => '1'],
        'failedJobs' => collect([]),
    ]);

    $page = Livewire::test(BatchPreview::class, ['batchId' => '1']);

    expect($page->call('getMaxContentWidth'))->toBe(Width::Full);
});

it('can access in local environment', function () {
    config()->set('app.env', 'local');

    expect(BatchPreview::canAccess())->toBeTrue();
});

it('checks authorization gate in production', function () {
    config()->set('app.env', 'production');

    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(BatchPreview::canAccess())->toBeTrue();

    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(BatchPreview::canAccess())->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
