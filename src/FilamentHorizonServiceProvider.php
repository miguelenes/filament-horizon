<?php

namespace Eloquage\FilamentHorizon;

use Eloquage\FilamentHorizon\Commands\FilamentHorizonCommand;
use Eloquage\FilamentHorizon\Services\HorizonApi;
use Eloquage\FilamentHorizon\Testing\TestsFilamentHorizon;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Bus\BatchRepository;
use Illuminate\Filesystem\Filesystem;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentHorizonServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-horizon';

    public static string $viewNamespace = 'filament-horizon';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->askToStarRepoOnGitHub('eloquage/filament-horizon');
            });

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(HorizonApi::class, function ($app) {
            return new HorizonApi(
                $app->make(JobRepository::class),
                $app->make(MetricsRepository::class),
                $app->make(TagRepository::class),
                $app->make(WorkloadRepository::class),
                $app->make(SupervisorRepository::class),
                $app->make(MasterSupervisorRepository::class),
                $app->make(BatchRepository::class),
            );
        });
    }

    /**
     * @throws \ReflectionException
     */
    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-horizon/{$file->getFilename()}"),
                ], 'filament-horizon-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentHorizon);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'eloquage/filament-horizon';
    }

    /**
     * @return array<\Filament\Support\Assets\Asset>
     */
    protected function getAssets(): array
    {
        return [
            Css::make('filament-horizon-styles', __DIR__ . '/../resources/dist/filament-horizon.css'),
            Js::make('filament-horizon-scripts', __DIR__ . '/../resources/dist/filament-horizon.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentHorizonCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }
}
