<?php

use Eloquage\FilamentHorizon\Pages\JobPreview;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Filament\Support\Enums\Width;
use Livewire\Livewire;

beforeEach(function () {
    $this->api = Mockery::mock(HorizonApi::class);
    app()->instance(HorizonApi::class, $this->api);
    config()->set('app.env', 'local');
});

it('can render job preview page', function () {
    $this->api->shouldReceive('getJob')->andReturn((object) ['id' => '1', 'payload' => (object) []]);

    Livewire::test(JobPreview::class, ['jobId' => '1'])
        ->assertSuccessful();
});

it('has correct title', function () {
    $this->api->shouldReceive('getJob')->andReturn((object) ['id' => '1', 'payload' => (object) []]);

    $page = Livewire::test(JobPreview::class, ['jobId' => '1']);

    expect($page->call('getTitle'))->toBe('Job Details');
});

it('mounts with job id', function () {
    $this->api->shouldReceive('getJob')->andReturn((object) ['id' => '1', 'payload' => (object) []]);

    Livewire::test(JobPreview::class, ['jobId' => 'job-123'])
        ->assertSet('jobId', 'job-123');
});

it('gets job from api', function () {
    $job = (object) ['id' => '1', 'payload' => (object) ['job' => 'test']];

    $this->api->shouldReceive('getJob')->with('1')->andReturn($job);

    $page = Livewire::test(JobPreview::class, ['jobId' => '1']);

    expect($page->call('getJob'))->toBe($job);
});

it('handles missing job gracefully', function () {
    $this->api->shouldReceive('getJob')->with('1')->andReturn(null);

    $page = Livewire::test(JobPreview::class, ['jobId' => '1']);

    expect($page->call('getJob'))->toBeNull();
});

it('extracts job base name correctly', function () {
    $this->api->shouldReceive('getJob')->andReturn((object) ['id' => '1', 'payload' => (object) []]);

    $page = Livewire::test(JobPreview::class, ['jobId' => '1']);
    $instance = $page->instance();

    $reflection = new ReflectionClass(JobPreview::class);
    $method = $reflection->getMethod('getJobBaseName');
    $method->setAccessible(true);

    expect($method->invoke($instance, 'App\Jobs\TestJob'))->toBe('TestJob');
});

it('formats timestamp correctly', function () {
    $this->api->shouldReceive('getJob')->andReturn((object) ['id' => '1', 'payload' => (object) []]);

    $page = Livewire::test(JobPreview::class, ['jobId' => '1']);
    $instance = $page->instance();

    $reflection = new ReflectionClass(JobPreview::class);
    $method = $reflection->getMethod('formatTimestamp');
    $method->setAccessible(true);

    $timestamp = now()->timestamp;
    $formatted = $method->invoke($instance, $timestamp);
    expect($formatted)->toBeString();
    expect($formatted)->toMatch('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/');

    expect($method->invoke($instance, null))->toBe('-');
});

it('handles float timestamp', function () {
    $this->api->shouldReceive('getJob')->andReturn((object) ['id' => '1', 'payload' => (object) []]);

    $page = Livewire::test(JobPreview::class, ['jobId' => '1']);
    $instance = $page->instance();

    $reflection = new ReflectionClass(JobPreview::class);
    $method = $reflection->getMethod('formatTimestamp');
    $method->setAccessible(true);

    $timestamp = 1767971179.3856;
    $formatted = $method->invoke($instance, $timestamp);
    expect($formatted)->toBeString();
});

it('has full width content', function () {
    $this->api->shouldReceive('getJob')->andReturn((object) ['id' => '1', 'payload' => (object) []]);

    $page = Livewire::test(JobPreview::class, ['jobId' => '1']);

    expect($page->instance()->getMaxContentWidth())->toBe(Width::Full);
});

it('can access in local environment', function () {
    config()->set('app.env', 'local');

    expect(JobPreview::canAccess())->toBeTrue();
});

it('checks authorization gate in production', function () {
    config()->set('app.env', 'production');

    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(JobPreview::canAccess())->toBeTrue();

    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(JobPreview::canAccess())->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
