<?php

namespace Eloquage\FilamentHorizon\Pages;

use BackedEnum;
use Carbon\Carbon;
use Eloquage\FilamentHorizon\Clusters\Horizon;
use Eloquage\FilamentHorizon\Concerns\AuthorizesHorizonAccess;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Url;

class FailedJobs extends Page
{
    use AuthorizesHorizonAccess;

    protected string $view = 'filament-horizon::pages.failed-jobs';

    protected static ?string $cluster = Horizon::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-x-circle';

    protected static ?int $navigationSort = 3;

    #[Url]
    public string $tagSearch = '';

    public int $page = 1;

    public int $perPage = 50;

    /** @var array<string> */
    public array $retryingJobs = [];

    public static function getNavigationLabel(): string
    {
        return __('filament-horizon::horizon.pages.failed_jobs.navigation_label');
    }

    public function getTitle(): string
    {
        return __('filament-horizon::horizon.pages.failed_jobs.title');
    }

    public function getJobs(): array
    {
        $api = app(HorizonApi::class);
        $startingAt = ($this->page - 1) * $this->perPage;
        $tag = $this->tagSearch ?: null;

        return $api->getFailedJobs($startingAt > 0 ? $startingAt - 1 : null, $tag);
    }

    public function getTotalPages(): int
    {
        $data = $this->getJobs();

        return (int) ceil(($data['total'] ?? 0) / $this->perPage);
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

    public function retryJob(string $id): void
    {
        if (in_array($id, $this->retryingJobs)) {
            return;
        }

        $this->retryingJobs[] = $id;

        $api = app(HorizonApi::class);
        $api->retryJob($id);

        Notification::make()
            ->title(__('filament-horizon::horizon.messages.job_retried'))
            ->success()
            ->send();

        // Remove from retrying after delay
        $this->dispatch('job-retry-complete', id: $id)->self();
    }

    public function jobRetryComplete(string $id): void
    {
        $this->retryingJobs = array_filter($this->retryingJobs, fn ($jobId) => $jobId !== $id);
    }

    protected function getJobBaseName(string $name): string
    {
        $parts = explode('\\', $name);

        return end($parts);
    }

    protected function formatTimestamp(?int $timestamp): string
    {
        if ($timestamp === null) {
            return '-';
        }

        return Carbon::createFromTimestamp($timestamp)->diffForHumans();
    }

    public function hasCompleted(object $job): bool
    {
        if (! isset($job->retried_by) || ! $job->retried_by instanceof \Illuminate\Support\Collection) {
            return false;
        }

        return $job->retried_by->contains(fn ($retry) => ($retry->status ?? null) === 'completed');
    }

    public function wasRetried(object $job): bool
    {
        return isset($job->retried_by) && $job->retried_by instanceof \Illuminate\Support\Collection && $job->retried_by->isNotEmpty();
    }

    public function getMaxContentWidth(): Width | null | string
    {
        return Width::Full;
    }
}
