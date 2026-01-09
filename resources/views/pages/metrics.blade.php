<x-filament-panels::page>
    <div wire:poll.10s>
        {{-- Header with tabs --}}
        <div style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <h3 class="fi-horizon-section-title">{{ __('filament-horizon::horizon.pages.metrics.title') }}</h3>
            <div style="display: flex; gap: 0.5rem;">
                <button
                    wire:click="setType('jobs')"
                    class="{{ $type === 'jobs' ? 'fi-horizon-badge fi-horizon-badge-primary' : '' }}"
                    style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; {{ $type === 'jobs' ? '' : 'background: var(--horizon-bg-code); color: var(--horizon-text-muted);' }}"
                >
                    {{ __('filament-horizon::horizon.pages.metrics.jobs') }}
                </button>
                <button
                    wire:click="setType('queues')"
                    class="{{ $type === 'queues' ? 'fi-horizon-badge fi-horizon-badge-primary' : '' }}"
                    style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; {{ $type === 'queues' ? '' : 'background: var(--horizon-bg-code); color: var(--horizon-text-muted);' }}"
                >
                    {{ __('filament-horizon::horizon.pages.metrics.queues') }}
                </button>
            </div>
        </div>

        {{-- Metrics Table --}}
        <div class="fi-horizon-card">
            @php $metrics = $this->getMetrics(); @endphp

            @if(!empty($metrics))
                <div style="overflow-x: auto;">
                    <table class="fi-horizon-table">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--horizon-border);">
                                <th>{{ __('filament-horizon::horizon.columns.name') }}</th>
                                <th style="text-align: right;">{{ __('filament-horizon::horizon.columns.throughput') }}</th>
                                <th style="text-align: right;">{{ __('filament-horizon::horizon.columns.runtime') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($metrics as $metric)
                                <tr>
                                    <td>
                                        <a href="{{ \Miguelenes\FilamentHorizon\Pages\MetricsPreview::getUrl(['type' => $type, 'metricSlug' => $metric['name']]) }}" class="fi-horizon-link">
                                            @if($type === 'jobs')
                                                {{ $this->getJobBaseName($metric['name']) }}
                                            @else
                                                {{ $metric['name'] }}
                                            @endif
                                        </a>
                                        @if($type === 'jobs')
                                            <div style="font-size: 0.75rem; color: var(--horizon-text-muted); margin-top: 0.25rem; font-family: monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 24rem;" title="{{ $metric['name'] }}">
                                                {{ $metric['name'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">{{ number_format($metric['throughput']) }}</td>
                                    <td style="text-align: right;">{{ $this->formatRuntime($metric['runtime']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="fi-horizon-empty">
                    {{ __('filament-horizon::horizon.messages.no_metrics') }}
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
