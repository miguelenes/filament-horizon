<?php

namespace Eloquage\FilamentHorizon\Pages;

use BackedEnum;
use Eloquage\FilamentHorizon\Clusters\Horizon;
use Eloquage\FilamentHorizon\Concerns\AuthorizesHorizonAccess;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class Monitoring extends Page
{
    use AuthorizesHorizonAccess;

    protected string $view = 'filament-horizon::pages.monitoring';

    protected static ?string $cluster = Horizon::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-eye';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-horizon::horizon.pages.monitoring.navigation_label');
    }

    public function getTitle(): string
    {
        return __('filament-horizon::horizon.pages.monitoring.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tag')
                    ->label('Tag to Monitor')
                    ->placeholder('Enter tag name...')
                    ->required()
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    public function getMonitoredTags(): Collection
    {
        $api = app(HorizonApi::class);

        return $api->getMonitoredTags();
    }

    public function startMonitoring(): void
    {
        $data = $this->form->getState();
        $tag = $data['tag'] ?? '';

        if (empty($tag)) {
            return;
        }

        $api = app(HorizonApi::class);
        $api->startMonitoring($tag);

        $this->form->fill();

        Notification::make()
            ->title(__('filament-horizon::horizon.messages.tag_monitoring_started'))
            ->success()
            ->send();
    }

    public function stopMonitoring(string $tag): void
    {
        $api = app(HorizonApi::class);
        $api->stopMonitoring($tag);

        Notification::make()
            ->title(__('filament-horizon::horizon.messages.tag_monitoring_stopped'))
            ->success()
            ->send();
    }

    public function getMaxContentWidth(): Width | null | string
    {
        return Width::Full;
    }
}
