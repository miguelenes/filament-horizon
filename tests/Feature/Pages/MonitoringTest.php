<?php

use Eloquage\FilamentHorizon\Pages\Monitoring;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

beforeEach(function () {
    $this->api = Mockery::mock(HorizonApi::class);
    app()->instance(HorizonApi::class, $this->api);
});

it('can render monitoring page', function () {
    $this->api->shouldReceive('getMonitoredTags')->andReturn(collect([]));

    Livewire::test(Monitoring::class)
        ->assertSuccessful();
});

it('has correct navigation label', function () {
    expect(Monitoring::getNavigationLabel())->toBeString();
});

it('has correct title', function () {
    $this->api->shouldReceive('getMonitoredTags')->andReturn(collect([]));

    $page = Livewire::test(Monitoring::class);

    expect($page->call('getTitle'))->toBeString();
});

it('has form with tag input', function () {
    $this->api->shouldReceive('getMonitoredTags')->andReturn(collect([]));

    Livewire::test(Monitoring::class)
        ->assertFormFieldExists('tag');
});

it('gets monitored tags', function () {
    $tags = collect([
        ['tag' => 'user:1', 'count' => 5],
        ['tag' => 'order:123', 'count' => 10],
    ]);

    $this->api->shouldReceive('getMonitoredTags')->andReturn($tags);

    $page = Livewire::test(Monitoring::class);

    expect($page->call('getMonitoredTags'))->toBeInstanceOf(Collection::class);
    expect($page->call('getMonitoredTags'))->toHaveCount(2);
});

it('can start monitoring a tag', function () {
    Bus::fake();

    $this->api->shouldReceive('getMonitoredTags')->andReturn(collect([]));
    $this->api->shouldReceive('startMonitoring')->with('user:1')->once();

    Livewire::test(Monitoring::class)
        ->fillForm(['tag' => 'user:1'])
        ->call('startMonitoring')
        ->assertNotified(Notification::make()
            ->title(__('filament-horizon::horizon.messages.tag_monitoring_started'))
            ->success());
});

it('does not start monitoring with empty tag', function () {
    $this->api->shouldReceive('getMonitoredTags')->andReturn(collect([]));
    $this->api->shouldNotReceive('startMonitoring');

    Livewire::test(Monitoring::class)
        ->fillForm(['tag' => ''])
        ->call('startMonitoring');
});

it('resets form after starting monitoring', function () {
    $this->api->shouldReceive('getMonitoredTags')->andReturn(collect([]));
    $this->api->shouldReceive('startMonitoring')->once();

    Livewire::test(Monitoring::class)
        ->fillForm(['tag' => 'user:1'])
        ->call('startMonitoring')
        ->assertFormSet(['tag' => null]);
});

it('can stop monitoring a tag', function () {
    Bus::fake();

    $this->api->shouldReceive('getMonitoredTags')->andReturn(collect([]));
    $this->api->shouldReceive('stopMonitoring')->with('user:1')->once();

    Livewire::test(Monitoring::class)
        ->call('stopMonitoring', 'user:1')
        ->assertNotified(Notification::make()
            ->title(__('filament-horizon::horizon.messages.tag_monitoring_stopped'))
            ->success());
});

it('has full width content', function () {
    $this->api->shouldReceive('getMonitoredTags')->andReturn(collect([]));

    $page = Livewire::test(Monitoring::class);

    expect($page->call('getMaxContentWidth'))->toBe(Width::Full);
});

it('can access in local environment', function () {
    config()->set('app.env', 'local');

    expect(Monitoring::canAccess())->toBeTrue();
});

it('checks authorization gate in production', function () {
    config()->set('app.env', 'production');

    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(Monitoring::canAccess())->toBeTrue();

    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(Monitoring::canAccess())->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
