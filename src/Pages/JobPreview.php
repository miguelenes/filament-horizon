<?php

namespace Eloquage\FilamentHorizon\Pages;

use BackedEnum;
use Carbon\Carbon;
use Eloquage\FilamentHorizon\Clusters\Horizon;
use Eloquage\FilamentHorizon\Concerns\AuthorizesHorizonAccess;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Width;

class JobPreview extends Page
{
    use AuthorizesHorizonAccess;

    protected static ?string $slug = 'job-preview';

    protected string $view = 'filament-horizon::pages.job-preview';

    protected static ?string $cluster = Horizon::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-document-text';

    protected static bool $shouldRegisterNavigation = false;

    public string $jobId = '';

    public static function getRoutePath(Panel $panel): string
    {
        return '/job-preview/{jobId}';
    }

    public function mount(string $jobId = ''): void
    {
        $this->jobId = $jobId;
    }

    public function getTitle(): string
    {
        return 'Job Details';
    }

    public function getJob(): ?object
    {
        $api = app(HorizonApi::class);

        return $api->getJob($this->jobId);
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

        return Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s');
    }

    public function getMaxContentWidth(): Width | null | string
    {
        return Width::Full;
    }
}
