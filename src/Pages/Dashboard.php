<?php

namespace Miguelenes\FilamentHorizon\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Enums\Width;
use Miguelenes\FilamentHorizon\Clusters\Horizon;
use Miguelenes\FilamentHorizon\Concerns\AuthorizesHorizonAccess;

class Dashboard extends Page
{
    use AuthorizesHorizonAccess;

    protected string $view = 'filament-horizon::pages.dashboard';

    protected static ?string $cluster = Horizon::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('filament-horizon::horizon.pages.dashboard.navigation_label');
    }

    public function getTitle(): string
    {
        return __('filament-horizon::horizon.pages.dashboard.title');
    }

    public function getMaxContentWidth(): Width|null|string
    {
        return Width::Full;
    }
}
