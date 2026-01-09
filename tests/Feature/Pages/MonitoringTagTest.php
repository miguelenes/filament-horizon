<?php

use Eloquage\FilamentHorizon\Pages\MonitoringTag;
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

it('can render monitoring tag page', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);

    Livewire::test(MonitoringTag::class, ['tag' => 'user:1'])
        ->assertSuccessful();
});

it('has correct title', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);

    $page = Livewire::test(MonitoringTag::class, ['tag' => 'user:1']);

    expect($page->call('getTitle'))->toBe('Tag: user:1');
});

it('mounts with tag', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);

    Livewire::test(MonitoringTag::class, ['tag' => 'user%3A1'])
        ->assertSet('tag', 'user:1');
});

it('gets tag jobs by default', function () {
    $this->api->shouldReceive('getTagJobs')
        ->with('user:1', 0, 25)
        ->andReturn(['jobs' => collect([]), 'total' => 0]);

    Livewire::test(MonitoringTag::class, ['tag' => 'user:1'])
        ->assertSet('type', 'jobs')
        ->call('getJobs');
});

it('gets tag failed jobs when type is failed', function () {
    $this->api->shouldReceive('getTagFailedJobs')
        ->with('user:1', 0, 25)
        ->andReturn(['jobs' => collect([]), 'total' => 0]);

    Livewire::test(MonitoringTag::class, ['tag' => 'user:1'])
        ->set('type', 'failed')
        ->call('getJobs');
});

it('calculates total pages correctly', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 100]);

    $page = Livewire::test(MonitoringTag::class, ['tag' => 'user:1']);

    expect($page->call('getTotalPages'))->toBe(4); // 100 / 25 per page
});

it('resets page when setting type', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);
    $this->api->shouldReceive('getTagFailedJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);

    Livewire::test(MonitoringTag::class, ['tag' => 'user:1'])
        ->set('page', 3)
        ->call('setType', 'failed')
        ->assertSet('page', 1);
});

it('can go to previous page', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 100]);

    Livewire::test(MonitoringTag::class, ['tag' => 'user:1'])
        ->set('page', 2)
        ->call('previousPage')
        ->assertSet('page', 1);
});

it('can go to next page', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 100]);

    Livewire::test(MonitoringTag::class, ['tag' => 'user:1'])
        ->set('page', 1)
        ->call('nextPage')
        ->assertSet('page', 2);
});

it('can retry job', function () {
    Bus::fake();

    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);
    $this->api->shouldReceive('retryJob')->with('job-1')->once();

    Livewire::test(MonitoringTag::class, ['tag' => 'user:1'])
        ->call('retryJob', 'job-1')
        ->assertSet('retryingJobs', ['job-1'])
        ->assertNotified(Notification::make()
            ->title(__('filament-horizon::horizon.messages.job_retried'))
            ->success());
});

it('prevents duplicate retries', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);
    $this->api->shouldReceive('retryJob')->once();

    Livewire::test(MonitoringTag::class, ['tag' => 'user:1'])
        ->set('retryingJobs', ['job-1'])
        ->call('retryJob', 'job-1')
        ->assertSet('retryingJobs', ['job-1']);
});

it('removes job from retrying array after completion', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);

    Livewire::test(MonitoringTag::class, ['tag' => 'user:1'])
        ->set('retryingJobs', ['job-1', 'job-2'])
        ->call('jobRetryComplete', 'job-1')
        ->assertSet('retryingJobs', ['job-2']);
});

it('extracts job base name correctly', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);

    $page = Livewire::test(MonitoringTag::class, ['tag' => 'user:1']);

    $reflection = new ReflectionClass(MonitoringTag::class);
    $method = $reflection->getMethod('getJobBaseName');
    $method->setAccessible(true);

    $instance = $page->instance();
    expect($method->invoke($instance, 'App\Jobs\TestJob'))->toBe('TestJob');
});

it('formats timestamp correctly', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);

    $page = Livewire::test(MonitoringTag::class, ['tag' => 'user:1']);

    $reflection = new ReflectionClass(MonitoringTag::class);
    $method = $reflection->getMethod('formatTimestamp');
    $method->setAccessible(true);

    $instance = $page->instance();
    $timestamp = now()->timestamp;
    expect($method->invoke($instance, $timestamp))->toBeString();
    expect($method->invoke($instance, null))->toBe('-');
});

it('handles float timestamp', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);

    $page = Livewire::test(MonitoringTag::class, ['tag' => 'user:1']);

    $reflection = new ReflectionClass(MonitoringTag::class);
    $method = $reflection->getMethod('formatTimestamp');
    $method->setAccessible(true);

    $instance = $page->instance();
    $timestamp = 1767971179.3856;
    $formatted = $method->invoke($instance, $timestamp);
    expect($formatted)->toBeString();
});

it('has full width content', function () {
    $this->api->shouldReceive('getTagJobs')->andReturn(['jobs' => collect([]), 'total' => 0]);

    $page = Livewire::test(MonitoringTag::class, ['tag' => 'user:1']);

    expect($page->call('getMaxContentWidth'))->toBe(Width::Full);
});

it('can access in local environment', function () {
    config()->set('app.env', 'local');

    expect(MonitoringTag::canAccess())->toBeTrue();
});

it('checks authorization gate in production when allowed', function () {
    config()->set('app.env', 'production');
    // Redefine the gate (Laravel 12 doesn't have forget method)
    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(MonitoringTag::canAccess())->toBeTrue();
});

it('checks authorization gate in production when denied', function () {
    config()->set('app.env', 'production');
    // Redefine the gate (Laravel 12 doesn't have forget method)
    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(MonitoringTag::canAccess())->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
