<?php

namespace Miguelenes\FilamentHorizon\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Miguelenes\FilamentHorizon\Clusters\Horizon;
use Miguelenes\FilamentHorizon\Concerns\AuthorizesHorizonAccess;
use Miguelenes\FilamentHorizon\Services\HorizonApi;

class Batches extends Page
{
    use AuthorizesHorizonAccess;

    protected string $view = 'filament-horizon::pages.batches';

    protected static ?string $cluster = Horizon::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 4;

    public ?string $beforeId = null;

    /** @var array<string> */
    public array $loadedBatchIds = [];

    public static function getNavigationLabel(): string
    {
        return __('filament-horizon::horizon.pages.batches.navigation_label');
    }

    public function getTitle(): string
    {
        return __('filament-horizon::horizon.pages.batches.title');
    }

    public function getBatches(): array
    {
        $api = app(HorizonApi::class);

        return $api->getBatches($this->beforeId);
    }

    public function loadMore(): void
    {
        $batches = $this->getBatches()['batches'] ?? [];
        if (! empty($batches)) {
            $lastBatch = end($batches);
            $this->beforeId = $lastBatch->id ?? null;
        }
    }

    protected function formatTimestamp(?string $timestamp): string
    {
        if ($timestamp === null) {
            return '-';
        }

        return Carbon::parse($timestamp)->diffForHumans();
    }

    protected function calculateProgress(object $batch): int
    {
        $total = $batch->totalJobs ?? 0;
        if ($total === 0) {
            return 0;
        }

        $pending = $batch->pendingJobs ?? 0;
        $processed = $total - $pending;

        return (int) round(($processed / $total) * 100);
    }

    public function getMaxContentWidth(): Width | null | string
    {
        return Width::Full;
    }
}
