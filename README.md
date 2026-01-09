<p align="center">
    <img src="https://raw.githubusercontent.com/miguelenes/filament-horizon/main/art/banner.png" alt="Filament Horizon Banner" style="width: 100%; max-width: 800px;">
</p>

<p align="center">
    <a href="https://packagist.org/packages/miguelenes/filament-horizon"><img src="https://img.shields.io/packagist/v/miguelenes/filament-horizon.svg?style=flat-square&label=version" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/miguelenes/filament-horizon"><img src="https://img.shields.io/packagist/php-v/miguelenes/filament-horizon.svg?style=flat-square" alt="PHP Version"></a>
    <a href="https://packagist.org/packages/miguelenes/filament-horizon"><img src="https://img.shields.io/packagist/dt/miguelenes/filament-horizon.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://github.com/miguelenes/filament-horizon/actions"><img src="https://img.shields.io/github/actions/workflow/status/miguelenes/filament-horizon/run-tests.yml?branch=main&style=flat-square&label=tests" alt="Tests"></a>
    <a href="https://github.com/miguelenes/filament-horizon/blob/main/LICENSE.md"><img src="https://img.shields.io/packagist/l/miguelenes/filament-horizon.svg?style=flat-square" alt="License"></a>
</p>

# Filament Horizon

> **The most beautiful way to monitor Laravel Horizon in your Filament admin panel.**

A seamless integration of [Laravel Horizon](https://laravel.com/docs/horizon) into [Filament v4](https://filamentphp.com), providing a stunning real-time dashboard to monitor your Redis queues, jobs, batches, and workers — all without leaving your admin panel.

---

## ✨ Features

<table>
<tr>
<td>

**📊 Real-time Dashboard**
- Live status indicators (running/paused/inactive)
- Jobs per minute throughput
- Process count & queue overview
- Max wait time monitoring

</td>
<td>

**🔄 Job Management**
- View pending, completed & silenced jobs
- Failed job inspection with stack traces
- One-click job retry functionality
- Tag-based job filtering

</td>
</tr>
<tr>
<td>

**📦 Batch Operations**
- Complete batch overview
- Progress tracking
- Failed job details per batch
- Batch retry capabilities

</td>
<td>

**📈 Advanced Metrics**
- Job throughput statistics
- Runtime performance data
- Queue-level analytics
- Historical snapshots

</td>
</tr>
<tr>
<td>

**🏷️ Tag Monitoring**
- Monitor specific tags in real-time
- Start/stop monitoring on demand
- Job counts per tag
- Failed jobs by tag

</td>
<td>

**👷 Worker Insights**
- Master supervisor status
- Per-supervisor breakdown
- Process allocation details
- Queue assignments

</td>
</tr>
</table>

---

## 📋 Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ^8.1 |
| Laravel | ^11.0 \| ^12.0 |
| Filament | ^4.0 |
| Laravel Horizon | ^5.0 |

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require miguelenes/filament-horizon
```

Register the plugin in your Filament panel provider:

```php
use Miguelenes\FilamentHorizon\FilamentHorizonPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            FilamentHorizonPlugin::make(),
        ]);
}
```

That's it! Navigate to your Filament panel and you'll see the **Horizon** cluster in the navigation.

---

## ⚙️ Configuration

### Publishing Config

Publish the configuration file to customize the package behavior:

```bash
php artisan vendor:publish --tag="filament-horizon-config"
```

### Publishing Views

Want to customize the look? Publish the views:

```bash
php artisan vendor:publish --tag="filament-horizon-views"
```

### Publishing Translations

Publish translations for localization:

```bash
php artisan vendor:publish --tag="filament-horizon-translations"
```

---

## 🔐 Authorization

By default, Filament Horizon allows access in `local` environments. For production, it uses Laravel's `viewHorizon` gate.

### Using Laravel's Gate

Define the gate in your `AuthServiceProvider` or a service provider:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewHorizon', function ($user) {
    return in_array($user->email, [
        'admin@example.com',
    ]);
});
```

### Disabling Authorization

For development or trusted environments, you can disable authorization entirely:

```php
FilamentHorizonPlugin::make()
    ->authorization(false),
```

---

## 🖥️ Available Pages

### Dashboard

The main overview page showing:
- **Status Banner** — Real-time Horizon status with visual indicators
- **Stats Grid** — Jobs processed, failed jobs, max wait time, total queues
- **Current Workload** — Per-queue job counts, processes, and wait times
- **Workers Panel** — Active supervisors with their configuration

### Recent Jobs

Browse through your queue history:
- **Pending** — Jobs waiting to be processed
- **Completed** — Successfully processed jobs
- **Silenced** — Jobs marked as silenced
- Filter by tags with the search functionality

### Failed Jobs

Manage problematic jobs:
- View exception details and stack traces
- See retry history and status
- One-click retry with instant feedback
- Filter failed jobs by tag

### Batches

Monitor your job batches:
- View batch progress percentage
- See pending and failed job counts
- Inspect failed jobs within batches
- Retry entire batches

### Monitoring

Tag-based monitoring system:
- Start monitoring specific tags
- View job counts per monitored tag
- Stop monitoring when no longer needed
- Quick access to tagged jobs

### Metrics

Performance analytics:
- **Jobs Tab** — Per-job throughput and runtime statistics
- **Queues Tab** — Per-queue performance metrics
- Visual representation of your queue performance

---

## 🎨 Widgets

The package includes reusable Filament widgets you can use in your own dashboards:

```php
use Miguelenes\FilamentHorizon\Widgets\StatsOverview;
use Miguelenes\FilamentHorizon\Widgets\WorkloadWidget;
use Miguelenes\FilamentHorizon\Widgets\WorkersWidget;
```

### StatsOverview Widget

Displays key Horizon statistics:
- Jobs per minute
- Recent jobs count
- Failed jobs count
- Current status
- Total processes
- Max wait time
- Max runtime queue
- Max throughput queue

---

## 🌐 Translations

Full localization support with the following keys:

```php
// Navigation
__('filament-horizon::horizon.navigation.label')

// Status indicators
__('filament-horizon::horizon.status.running')
__('filament-horizon::horizon.status.paused')
__('filament-horizon::horizon.status.inactive')

// And many more...
```

Currently available in:
- 🇺🇸 English

PRs for additional languages are welcome!

---

## 🔄 Real-time Updates

All pages feature automatic polling to keep data fresh:

| Component | Polling Interval |
|-----------|-----------------|
| Dashboard | 5 seconds |
| Stats Widget | 5 seconds |
| Job Lists | On demand |

---

## 🧪 Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

---

## 📸 Screenshots

<details>
<summary>Click to view screenshots</summary>

### Dashboard
![Dashboard](https://raw.githubusercontent.com/miguelenes/filament-horizon/main/art/dashboard.png)

### Failed Jobs
![Failed Jobs](https://raw.githubusercontent.com/miguelenes/filament-horizon/main/art/failed-jobs.png)

### Metrics
![Metrics](https://raw.githubusercontent.com/miguelenes/filament-horizon/main/art/metrics.png)

### Batches
![Batches](https://raw.githubusercontent.com/miguelenes/filament-horizon/main/art/batches.png)

</details>

---

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Format code: `composer format`

---

## 🔒 Security

If you discover a security vulnerability, please send an email to [carlos.miguel.enes@gmail.com](mailto:carlos.miguel.enes@gmail.com). All security vulnerabilities will be promptly addressed.

---

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## 💖 Credits

- [Miguel Enes](https://github.com/miguelenes) — Creator & Maintainer
- [Laravel Horizon](https://github.com/laravel/horizon) — The amazing queue dashboard this package builds upon
- [Filament](https://filamentphp.com) — The beautiful admin panel framework
- [All Contributors](../../contributors)

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

<p align="center">
    <strong>Built with ❤️ for the Laravel & Filament community</strong>
</p>

<p align="center">
    <a href="https://github.com/miguelenes/filament-horizon">⭐ Star us on GitHub</a>
    &nbsp;·&nbsp;
    <a href="https://github.com/miguelenes/filament-horizon/issues">🐛 Report Bug</a>
    &nbsp;·&nbsp;
    <a href="https://github.com/miguelenes/filament-horizon/issues">💡 Request Feature</a>
</p>
