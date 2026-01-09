<?php

use Eloquage\FilamentHorizon\Clusters\Horizon;
use Illuminate\Support\Facades\Gate;

it('has correct navigation icon', function () {
    $reflection = new ReflectionClass(Horizon::class);
    $property = $reflection->getProperty('navigationIcon');
    $property->setAccessible(true);

    expect($property->getValue())->toBe('heroicon-o-queue-list');
});

it('has correct navigation label', function () {
    expect(Horizon::getNavigationLabel())->toBeString();
});

it('has correct cluster breadcrumb', function () {
    expect(Horizon::getClusterBreadcrumb())->toBeString();
});

it('has correct navigation sort', function () {
    $reflection = new ReflectionClass(Horizon::class);
    $property = $reflection->getProperty('navigationSort');
    $property->setAccessible(true);

    expect($property->getValue())->toBe(100);
});

it('allows access in local environment', function () {
    config()->set('app.env', 'local');

    expect(Horizon::canAccess())->toBeTrue();
});

it('checks gate in production when allowed', function () {
    config()->set('app.env', 'production');

    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(Horizon::canAccess())->toBeTrue();
});

it('checks gate in production when denied', function () {
    config()->set('app.env', 'production');

    Gate::forget('viewHorizon');
    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(Horizon::canAccess())->toBeFalse();
});
