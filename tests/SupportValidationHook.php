<?php

namespace Eloquage\FilamentHorizon\Tests;

use Illuminate\Support\MessageBag;
use Livewire\ComponentHook;

class SupportValidationHook extends ComponentHook
{
    public function skip()
    {
        // Don't skip - we want this hook to always run
        return false;
    }

    public function boot()
    {
        // Ensure component always has an error bag initialized
        // This runs very early, before any other hooks
        $store = \Livewire\store($this->component);

        // Force set the error bag directly in the store to ensure it exists
        if (! $store->has('errorBag')) {
            $store->set('errorBag', new MessageBag);
        } else {
            // Even if it exists, verify it's not null
            $existing = $store->get('errorBag');
            if ($existing === null) {
                $store->set('errorBag', new MessageBag);
            }
        }

        // Also call setErrorBag to ensure the component knows about it
        $this->component->setErrorBag($store->get('errorBag') ?? new MessageBag);
    }

    public function hydrate($memo)
    {
        // Ensure component always has an error bag initialized BEFORE SupportValidation runs
        // This runs for every component hydration, ensuring error bags are always set
        $store = \Livewire\store($this->component);
        if (! $store->has('errorBag')) {
            $this->component->setErrorBag(new MessageBag);
        }
    }

    public function mount($params, $parent)
    {
        // Also ensure error bag is initialized on mount
        $store = \Livewire\store($this->component);
        if (! $store->has('errorBag')) {
            $this->component->setErrorBag(new MessageBag);
        }
    }

    public function render($view, $data)
    {
        // CRITICAL FIX: Ensure error bag exists BEFORE SupportValidation tries to use it
        // We run this in render to catch it right before SupportValidation's render

        // Get the store and ensure errorBag exists and is not null
        $store = \Livewire\store($this->component);

        // Force ensure errorBag exists - check and set multiple ways
        if (! $store->has('errorBag')) {
            $store->set('errorBag', new MessageBag);
        }

        $errorBag = $store->get('errorBag');
        if (! ($errorBag instanceof MessageBag)) {
            $errorBag = new MessageBag;
            $store->set('errorBag', $errorBag);
        }

        // Also ensure component's setErrorBag is called to sync state
        $this->component->setErrorBag($errorBag);

        // Verify one more time via getErrorBag() - if still null, force it
        $verifiedBag = $this->component->getErrorBag();
        if ($verifiedBag === null || ! ($verifiedBag instanceof MessageBag)) {
            $store->set('errorBag', new MessageBag);
            $this->component->setErrorBag(new MessageBag);
        }

        // Now share errors (this is what SupportValidation does, but we ensure it's safe)
        // Note: SupportValidation will also run, but now getErrorBag() should never return null
        // We don't override SupportValidation's render, we just ensure the error bag exists
    }
}
