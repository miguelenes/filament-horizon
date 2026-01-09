<?php

use Eloquage\FilamentHorizon\Commands\FilamentHorizonCommand;
use Illuminate\Support\Facades\Artisan;

it('has correct command signature', function () {
    $command = new FilamentHorizonCommand;

    expect($command->getName())->toBe('filament-horizon');
});

it('has correct command description', function () {
    $command = new FilamentHorizonCommand;

    expect($command->getDescription())->toBe('My command');
});

it('executes command successfully', function () {
    Artisan::call('filament-horizon');

    expect(Artisan::output())->toContain('All done');
    expect(Artisan::call('filament-horizon'))->toBe(0);
});
