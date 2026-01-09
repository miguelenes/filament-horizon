<?php

namespace Miguelenes\FilamentHorizon;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentHorizonPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-horizon';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
