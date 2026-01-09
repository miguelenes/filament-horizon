<?php

use Eloquage\FilamentHorizon\Pages\Dashboard;
use Illuminate\Support\Facades\Gate;

it('allows access in local environment', function () {
    config()->set('app.env', 'local');

    expect(Dashboard::canAccess())->toBeTrue();
});

it('checks gate in production when allowed', function () {
    config()->set('app.env', 'production');

    Gate::define('viewHorizon', fn ($user = null) => true);

    expect(Dashboard::canAccess())->toBeTrue();
});

it('checks gate in production when denied', function () {
    config()->set('app.env', 'production');

    // Redefine the gate (Laravel 12 doesn't have forget method)
    Gate::define('viewHorizon', fn ($user = null) => false);

    expect(Dashboard::canAccess())->toBeFalse();
});

it('works on pages using the trait', function () {
    config()->set('app.env', 'local');

    $pages = [
        \Eloquage\FilamentHorizon\Pages\Dashboard::class,
        \Eloquage\FilamentHorizon\Pages\RecentJobs::class,
        \Eloquage\FilamentHorizon\Pages\FailedJobs::class,
    ];

    foreach ($pages as $page) {
        expect($page::canAccess())->toBeTrue();
    }
});
