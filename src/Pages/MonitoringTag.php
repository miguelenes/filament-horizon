<?php

namespace Miguelenes\FilamentHorizon\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Url;
use Miguelenes\FilamentHorizon\Clusters\Horizon;
use Miguelenes\FilamentHorizon\Concerns\AuthorizesHorizonAccess;
use Miguelenes\FilamentHorizon\Services\HorizonApi;

class MonitoringTag extends Page
{
    use AuthorizesHorizonAccess;

    protected static ?string $slug = 'monitoring-tag';

    protected string $view = 'filament-horizon::pages.monitoring-tag';

    protected static ?string $cluster = Horizon::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-tag';

    protected static bool $shouldRegisterNavigation = false;

    public string $tag = '';

    #[Url]
    public string $type = 'jobs';

    public int $page = 1;

    public int $perPage = 25;

    /** @var array<string> */
    public array $retryingJobs = [];

    public static function getRoutePath(Panel $panel): string
    {
        return '/monitoring-tag/{tag}';
    }

    public function mount(string $tag = ''): void
    {
        $this->tag = urldecode($tag);
    }

    public function getTitle(): string
    {
        return "Tag: {$this->tag}";
    }

    public function getJobs(): array
    {
        $api = app(HorizonApi::class);
        $startingAt = ($this->page - 1) * $this->perPage;

        if ($this->type === 'failed') {
            return $api->getTagFailedJobs($this->tag, $startingAt, $this->perPage);
        }

        return $api->getTagJobs($this->tag, $startingAt, $this->perPage);
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

    public function getMaxContentWidth(): Width | null | string
    {
        return Width::Full;
    }
}
