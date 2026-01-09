<?php

use Eloquage\FilamentHorizon\Pages\Batches;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Filament\Support\Enums\Width;
use Livewire\Livewire;

beforeEach(function () {
    $this->api = Mockery::mock(HorizonApi::class);
    app()->instance(HorizonApi::class, $this->api);
});

it('can render batches page', function () {
    $this->api->shouldReceive('getBatches')->andReturn(['batches' => []]);

    Livewire::test(Batches::class)
        ->assertSuccessful();
});

it('has correct navigation label', function () {
    expect(Batches::getNavigationLabel())->toBeString();
});

it('has correct title', function () {
    $this->api->shouldReceive('getBatches')->andReturn(['batches' => []]);

    $page = Livewire::test(Batches::class);

    expect($page->call('getTitle'))->toBeString();
});

it('gets batches without beforeId', function () {
    $this->api->shouldReceive('getBatches')
        ->with(null)
        ->andReturn(['batches' => []]);

    Livewire::test(Batches::class)
        ->call('getBatches');
});

it('gets batches with beforeId', function () {
    $batch1 = (object) ['id' => 'batch-1'];
    $batch2 = (object) ['id' => 'batch-2'];

    $this->api->shouldReceive('getBatches')
        ->with(null)
        ->andReturn(['batches' => [$batch1, $batch2]]);

    $this->api->shouldReceive('getBatches')
        ->with('batch-2')
        ->andReturn(['batches' => []]);

    Livewire::test(Batches::class)
        ->call('loadMore')
        ->assertSet('beforeId', 'batch-2');
});

it('updates beforeId when loading more', function () {
    $batch1 = (object) ['id' => 'batch-1'];
    $batch2 = (object) ['id' => 'batch-2'];

    $this->api->shouldReceive('getBatches')
        ->andReturn(['batches' => [$batch1, $batch2]]);

    Livewire::test(Batches::class)
        ->call('loadMore')
        ->assertSet('beforeId', 'batch-2');
});

it('does not update beforeId when no batches', function () {
    $this->api->shouldReceive('getBatches')
        ->andReturn(['batches' => []]);

    Livewire::test(Batches::class)
        ->set('beforeId', null)
        ->call('loadMore')
        ->assertSet('beforeId', null);
});

it('formats timestamp correctly', function () {
    $this->api->shouldReceive('getBatches')->andReturn(['batches' => []]);

    $page = Livewire::test(Batches::class);

    $reflection = new ReflectionClass(Batches::class);
    $instance = $page->instance();
    $method = $reflection->getMethod('formatTimestamp');
    $method->setAccessible(true);

    $timestamp = now()->toIso8601String();
    expect($method->invoke($instance, $timestamp))->toBeString();
    expect($method->invoke($instance, null))->toBe('-');
});

it('calculates progress correctly', function () {
    $this->api->shouldReceive('getBatches')->andReturn(['batches' => []]);

    $page = Livewire::test(Batches::class);

    $reflection = new ReflectionClass(Batches::class);
    $instance = $page->instance();
    $method = $reflection->getMethod('calculateProgress');
    $method->setAccessible(true);

    $batch = (object) ['totalJobs' => 100, 'pendingJobs' => 50];
    expect($method->invoke($instance, $batch))->toBe(50);

    $batchComplete = (object) ['totalJobs' => 100, 'pendingJobs' => 0];
    expect($method->invoke($instance, $batchComplete))->toBe(100);

    $batchEmpty = (object) ['totalJobs' => 0, 'pendingJobs' => 0];
    expect($method->invoke($instance, $batchEmpty))->toBe(0);
});

it('has full width content', function () {
    $this->api->shouldReceive('getBatches')->andReturn(['batches' => []]);

    $page = Livewire::test(Batches::class);

    expect($page->call('getMaxContentWidth'))->toBe(Width::Full);
});

it('can access in local environment', function () {
    config()->set('app.env', 'local');

    expect(Batches::canAccess())->toBeTrue();
});

it('checks authorization gate in production', function () {
    config()->set('app.env', 'production');

    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(Batches::canAccess())->toBeTrue();

    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(Batches::canAccess())->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
