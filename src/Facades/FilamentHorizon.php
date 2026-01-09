<?php

namespace Miguelenes\FilamentHorizon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Miguelenes\FilamentHorizon\FilamentHorizon
 */
class FilamentHorizon extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Miguelenes\FilamentHorizon\FilamentHorizon::class;
    }
}
