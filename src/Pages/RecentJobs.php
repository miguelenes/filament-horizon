<?php

namespace Miguelenes\FilamentHorizon\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Url;
use Miguelenes\FilamentHorizon\Clusters\Horizon;
use Miguelenes\FilamentHorizon\Concerns\AuthorizesHorizonAccess;
use Miguelenes\FilamentHorizon\Services\HorizonApi;

class RecentJobs extends Page
{
    use AuthorizesHorizonAccess;

    protected string $view = 'filament-horizon::pages.recent-jobs';

    protected static ?string $cluster = Horizon::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?int $navigationSort = 2;

    #[Url]
    public string $type = 'pending';

    #[Url]
    public string $tagSearch = '';

    public int $page = 1;

    public int $perPage = 50;

    public static function getNavigationLabel(): string
    {
        return __('filament-horizon::horizon.pages.jobs.navigation_label');
    }

    public function getTitle(): string
    {
        return __('filament-horizon::horizon.pages.jobs.title');
    }

    public function getJobs(): array
    {
        $api = app(HorizonApi::class);
        $startingAt = ($this->page - 1) * $this->perPage;
        $tag = $this->tagSearch ?: null;

        return match ($this->type) {
            'pending' => $api->getPendingJobs($startingAt > 0 ? $startingAt - 1 : null, $tag),
            'completed' => $api->getCompletedJobs($startingAt > 0 ? $startingAt - 1 : null, $tag),
            'silenced' => $api->getSilencedJobs($startingAt > 0 ? $startingAt - 1 : null, $tag),
            default => $api->getPendingJobs($startingAt > 0 ? $startingAt - 1 : null, $tag),
        };
    }

    public function getTotalPages(): int
    {
        $data = $this->getJobs();

        return (int) ceil(($data['total'] ?? 0) / $this->perPage);
    }

    public function setType(string $type): void
    {
        $this->type = $type;
        $this->page = 1;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage(): void
    {
        if ($this->page < $this->getTotalPages()) {
            $this->page++;
        }
    }

    public function updatedTagSearch(): void
    {
        $this->page = 1;
    }

    protected function getJobBaseName(string $name): string
    {
        $parts = explode('\\', $name);

        return end($parts);
    }

    protected function formatRuntime(?float $runtime): string
    {
        if ($runtime === null) {
            return '-';
        }

        return number_format($runtime / 1000, 2) . 's';
    }

    protected function formatTimestamp(?int $timestamp): string
    {
        if ($timestamp === null) {
            return '-';
        }

        return \Carbon\Carbon::createFromTimestamp($timestamp)->diffForHumans();
    }

    public function getMaxContentWidth(): Width|null|string
    {
        return Width::Full;
    }
}
