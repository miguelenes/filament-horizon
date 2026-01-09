<?php

namespace Eloquage\FilamentHorizon\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Eloquage\FilamentHorizon\FilamentHorizonPlugin;
use Eloquage\FilamentHorizon\FilamentHorizonServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Laravel\Horizon\HorizonServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Eloquage\\FilamentHorizon\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        // Define the viewHorizon gate for testing (accepts user parameter for proper authorization checks)
        Gate::define('viewHorizon', fn ($user = null) => true);

        // Initialize view error bag for Livewire testing
        // Livewire expects 'errors' to be in shared view data (see HandlesValidation.php line 45)
        // Ensure it has a default MessageBag to prevent null issues
        $errorBag = new ViewErrorBag;
        $errorBag->put('default', new MessageBag);
        view()->share('errors', $errorBag);
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            HorizonServiceProvider::class,
            FilamentHorizonServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('horizon.use', 'default');
        config()->set('queue.default', 'redis');
    }
}

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->plugin(FilamentHorizonPlugin::make());
    }
}
