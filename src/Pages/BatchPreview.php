<?php

namespace Miguelenes\FilamentHorizon\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Miguelenes\FilamentHorizon\Clusters\Horizon;
use Miguelenes\FilamentHorizon\Concerns\AuthorizesHorizonAccess;
use Miguelenes\FilamentHorizon\Services\HorizonApi;

class BatchPreview extends Page
{
    use AuthorizesHorizonAccess;

    protected static ?string $slug = 'batch-preview';

    protected string $view = 'filament-horizon::pages.batch-preview';

    protected static ?string $cluster = Horizon::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-document-text';

    protected static bool $shouldRegisterNavigation = false;

    public string $batchId = '';

    public bool $isRetrying = false;

    public static function getRoutePath(Panel $panel): string
    {
        return '/batch-preview/{batchId}';
    }

    public function mount(string $batchId = ''): void
    {
        $this->batchId = $batchId;
    }

    public function getTitle(): string
    {
        return 'Batch Details';
    }

    public function getBatch(): array
    {
        $api = app(HorizonApi::class);

        return $api->getBatch($this->batchId);
    }

    public function retryBatch(): void
    {
        if ($this->isRetrying) {
            return;
        }

        $this->isRetrying = true;

        $api = app(HorizonApi::class);
        $api->retryBatch($this->batchId);

        Notification::make()
            ->title(__('filament-horizon::horizon.messages.batch_retried'))
            ->success()
            ->send();
    }

    protected function formatTimestamp(?string $timestamp): string
    {
        if ($timestamp === null) {
            return '-';
        }

        return Carbon::parse($timestamp)->format('Y-m-d H:i:s');
    }

    protected function calculateProgress(?object $batch): int
    {
        if (! $batch) {
            return 0;
        }

        $total = $batch->totalJobs ?? 0;
        if ($total === 0) {
            return 0;
        }

        $pending = $batch->pendingJobs ?? 0;
        $processed = $total - $pending;

        return (int) round(($processed / $total) * 100);
    }

    protected function getJobBaseName(string $name): string
    {
        $parts = explode('\\', $name);

        return end($parts);
    }

    public function getMaxContentWidth(): Width|null|string
    {
        return Width::Full;
    }
}
