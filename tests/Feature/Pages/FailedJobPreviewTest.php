<?php

use Eloquage\FilamentHorizon\Pages\FailedJobPreview;
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

it('can render failed job preview page', function () {
    $this->api->shouldReceive('getFailedJob')->andReturn((object) [
        'id' => '1',
        'payload' => (object) [],
        'exception' => 'Error',
        'context' => (object) [],
        'retried_by' => collect([]),
    ]);

    Livewire::test(FailedJobPreview::class, ['jobId' => '1'])
        ->assertSuccessful();
});

it('has correct title', function () {
    $this->api->shouldReceive('getFailedJob')->andReturn((object) [
        'id' => '1',
        'payload' => (object) [],
        'exception' => 'Error',
        'context' => (object) [],
        'retried_by' => collect([]),
    ]);

    $page = Livewire::test(FailedJobPreview::class, ['jobId' => '1']);

    expect($page->call('getTitle'))->toBe('Failed Job Details');
});

it('mounts with job id', function () {
    $this->api->shouldReceive('getFailedJob')->andReturn((object) [
        'id' => '1',
        'payload' => (object) [],
        'exception' => 'Error',
        'context' => (object) [],
        'retried_by' => collect([]),
    ]);

    Livewire::test(FailedJobPreview::class, ['jobId' => 'job-123'])
        ->assertSet('jobId', 'job-123');
});

it('gets failed job from api', function () {
    $job = (object) [
        'id' => '1',
        'payload' => (object) ['job' => 'test'],
        'exception' => 'Error',
        'context' => (object) [],
        'retried_by' => collect([]),
    ];

    $this->api->shouldReceive('getFailedJob')->with('1')->andReturn($job);

    $page = Livewire::test(FailedJobPreview::class, ['jobId' => '1']);

    expect($page->call('getJob'))->toBe($job);
});

it('handles missing job gracefully', function () {
    $this->api->shouldReceive('getFailedJob')->with('1')->andReturn(null);

    $page = Livewire::test(FailedJobPreview::class, ['jobId' => '1']);

    expect($page->call('getJob'))->toBeNull();
});

it('can retry failed job', function () {
    Bus::fake();

    $this->api->shouldReceive('getFailedJob')->andReturn((object) [
        'id' => '1',
        'payload' => (object) [],
        'exception' => 'Error',
        'context' => (object) [],
        'retried_by' => collect([]),
    ]);
    $this->api->shouldReceive('retryJob')->with('1')->once();

    Livewire::test(FailedJobPreview::class, ['jobId' => '1'])
        ->call('retryJob')
        ->assertSet('isRetrying', true)
        ->assertNotified(Notification::make()
            ->title(__('filament-horizon::horizon.messages.job_retried'))
            ->success());
});

it('prevents duplicate retries', function () {
    $this->api->shouldReceive('getFailedJob')->andReturn((object) [
        'id' => '1',
        'payload' => (object) [],
        'exception' => 'Error',
        'context' => (object) [],
        'retried_by' => collect([]),
    ]);
    $this->api->shouldNotReceive('retryJob');

    Livewire::test(FailedJobPreview::class, ['jobId' => '1'])
        ->set('isRetrying', true)
        ->call('retryJob')
        ->assertSet('isRetrying', true);
});

it('checks if job has completed retry', function () {
    $this->api->shouldReceive('getFailedJob')->andReturn((object) [
        'id' => '1',
        'payload' => (object) [],
        'exception' => 'Error',
        'context' => (object) [],
        'retried_by' => collect([]),
    ]);

    $page = Livewire::test(FailedJobPreview::class, ['jobId' => '1']);

    $job = (object) [
        'retried_by' => collect([
            (object) ['status' => 'completed'],
        ]),
    ];

    expect($page->call('hasCompleted', $job))->toBeTrue();

    $jobNoCompleted = (object) [
        'retried_by' => collect([
            (object) ['status' => 'failed'],
        ]),
    ];

    expect($page->call('hasCompleted', $jobNoCompleted))->toBeFalse();

    expect($page->call('hasCompleted', null))->toBeFalse();
});

it('extracts job base name correctly', function () {
    $this->api->shouldReceive('getFailedJob')->andReturn((object) [
        'id' => '1',
        'payload' => (object) [],
        'exception' => 'Error',
        'context' => (object) [],
        'retried_by' => collect([]),
    ]);

    $page = Livewire::test(FailedJobPreview::class, ['jobId' => '1']);

    $instance = $page->instance();
    $reflection = new ReflectionClass(FailedJobPreview::class);
    $method = $reflection->getMethod('getJobBaseName');
    $method->setAccessible(true);

    expect($method->invoke($instance, 'App\Jobs\TestJob'))->toBe('TestJob');
});

it('formats timestamp correctly', function () {
    $this->api->shouldReceive('getFailedJob')->andReturn((object) [
        'id' => '1',
        'payload' => (object) [],
        'exception' => 'Error',
        'context' => (object) [],
        'retried_by' => collect([]),
    ]);

    $page = Livewire::test(FailedJobPreview::class, ['jobId' => '1']);

    $instance = $page->instance();
    $reflection = new ReflectionClass(FailedJobPreview::class);
    $method = $reflection->getMethod('formatTimestamp');
    $method->setAccessible(true);

    $timestamp = now()->timestamp;
    $formatted = $method->invoke($instance, $timestamp);
    expect($formatted)->toBeString();
    expect($formatted)->toMatch('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/');

    expect($method->invoke($instance, null))->toBe('-');
});

it('has full width content', function () {
    $this->api->shouldReceive('getFailedJob')->andReturn((object) [
        'id' => '1',
        'payload' => (object) [],
        'exception' => 'Error',
        'context' => (object) [],
        'retried_by' => collect([]),
    ]);

    $page = Livewire::test(FailedJobPreview::class, ['jobId' => '1']);

    expect($page->call('getMaxContentWidth'))->toBe(Width::Full);
});

it('can access in local environment', function () {
    config()->set('app.env', 'local');

    expect(FailedJobPreview::canAccess())->toBeTrue();
});

it('checks authorization gate in production', function () {
    config()->set('app.env', 'production');

    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(FailedJobPreview::canAccess())->toBeTrue();

    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(FailedJobPreview::canAccess())->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
