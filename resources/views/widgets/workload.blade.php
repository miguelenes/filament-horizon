<x-filament-widgets::widget>
    <div wire:poll.5s class="fi-horizon-card">
        <div class="fi-horizon-card-header">
            <h3 class="fi-horizon-section-title">{{ __('filament-horizon::horizon.widgets.workload.title') }}</h3>
        </div>

        @if($workload->isNotEmpty())
            <div style="overflow-x: auto;">
                <table class="fi-horizon-table">
                    <thead>
                        <tr style="background: var(--horizon-bg-tertiary);">
                            <th style="padding: 0.5rem 0.75rem;">{{ __('filament-horizon::horizon.widgets.workload.queue') }}</th>
                            <th style="padding: 0.5rem 0.75rem; text-align: right;">{{ __('filament-horizon::horizon.widgets.workload.jobs') }}</th>
                            <th style="padding: 0.5rem 0.75rem; text-align: right;">{{ __('filament-horizon::horizon.widgets.workload.processes') }}</th>
                            <th style="padding: 0.5rem 0.75rem; text-align: right;">{{ __('filament-horizon::horizon.widgets.workload.wait') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($workload as $queue)
                            <tr style="{{ !empty($queue['split_queues']) ? 'background: var(--horizon-bg-tertiary);' : '' }}">
                                <td style="padding: 0.5rem 0.75rem; {{ !empty($queue['split_queues']) ? 'font-weight: 600;' : '' }}">
                                    <code class="fi-horizon-code">{{ str_replace(',', ', ', $queue['name']) }}</code>
                                </td>
                                <td style="padding: 0.5rem 0.75rem; text-align: right;">
                                    @if(($queue['length'] ?? 0) > 0)
                                        <span class="fi-horizon-badge fi-horizon-badge-primary">{{ number_format($queue['length'] ?? 0) }}</span>
                                    @else
                                        <span style="color: var(--horizon-text-muted);">0</span>
                                    @endif
                                </td>
                                <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ number_format($queue['processes'] ?? 0) }}</td>
                                <td style="padding: 0.5rem 0.75rem; text-align: right;">
                                    @php $wait = $queue['wait'] ?? 0; @endphp
                                    @if($wait > 60)
                                        <span class="fi-horizon-badge fi-horizon-badge-warning">{{ $queue['wait_formatted'] }}</span>
                                    @else
                                        <span style="color: var(--horizon-text-muted);">{{ $queue['wait_formatted'] }}</span>
                                    @endif
                                </td>
                            </tr>
                            @if(!empty($queue['split_queues']))
                                @foreach($queue['split_queues'] as $splitQueue)
                                    <tr>
                                        <td style="padding: 0.5rem 0.75rem; padding-left: 2rem;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <span style="color: var(--horizon-text-muted);">└</span>
                                                <code class="fi-horizon-code">{{ str_replace(',', ', ', $splitQueue['name']) }}</code>
                                            </div>
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: right;">
                                            @if(($splitQueue['length'] ?? 0) > 0)
                                                <span class="fi-horizon-badge fi-horizon-badge-primary">{{ number_format($splitQueue['length'] ?? 0) }}</span>
                                            @else
                                                <span style="color: var(--horizon-text-muted);">0</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem; color: var(--horizon-text-muted); text-align: right;">-</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ \Carbon\CarbonInterval::seconds($splitQueue['wait'] ?? 0)->cascade()->forHumans(['short' => true]) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="fi-horizon-empty" style="padding: 2rem;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 9999px; background: var(--horizon-bg-code); margin-bottom: 0.75rem;">
                    <svg style="width: 1.5rem; height: 1.5rem; color: var(--horizon-text-muted);" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                    </svg>
                </div>
                <p style="font-size: 0.875rem; color: var(--horizon-text-muted);">{{ __('filament-horizon::horizon.messages.no_jobs') }}</p>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
