<?php

namespace Eloquage\FilamentHorizon\Tests;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportValidation\SupportValidation;

class TestLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Patch SupportValidation's render method to handle null error bags
        // This runs after LivewireServiceProvider, so we can patch the class
        if (class_exists(SupportValidation::class)) {
            $this->patchSupportValidation();
        }
    }

    protected function patchSupportValidation(): void
    {
        // The hook is already registered in TestCase::setUp()
        // But we need to ensure it runs before SupportValidation
        // Since hooks run in registration order, and SupportValidation is registered
        // by LivewireServiceProvider, we need to ensure our hook is registered first
        // This is already done in TestCase::setUp() before LivewireServiceProvider boots
    }
}
