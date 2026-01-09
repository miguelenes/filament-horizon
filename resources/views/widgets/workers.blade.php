<x-filament-widgets::widget>
    <div wire:poll.5s class="fi-horizon-card">
        <div class="fi-horizon-card-header">
            <h3 class="fi-horizon-section-title">{{ __('filament-horizon::horizon.widgets.workers.title') }}</h3>
        </div>
        <div class="fi-horizon-card-body">
            @forelse($workers as $worker)
                <div style="{{ !$loop->last ? 'margin-bottom: 1.5rem;' : '' }}">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--horizon-border);">
                        <span style="font-weight: 600; color: var(--horizon-text-primary);">{{ $worker->name }}</span>
                        @php $statusText = $worker->status === 'running' ? 'Running' : ($worker->status === 'paused' ? 'Paused' : 'Inactive'); @endphp
                        <span class="fi-horizon-badge fi-horizon-badge-{{ $worker->status === 'running' ? 'success' : ($worker->status === 'paused' ? 'warning' : 'danger') }}">{{ $statusText }}</span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="fi-horizon-table">
                            <thead>
                                <tr style="background: var(--horizon-bg-tertiary);">
                                    <th style="padding: 0.5rem 0.75rem;">{{ __('filament-horizon::horizon.widgets.workers.supervisor') }}</th>
                                    <th style="padding: 0.5rem 0.75rem;">{{ __('filament-horizon::horizon.widgets.workers.connection') }}</th>
                                    <th style="padding: 0.5rem 0.75rem;">{{ __('filament-horizon::horizon.widgets.workers.queues') }}</th>
                                    <th style="padding: 0.5rem 0.75rem; text-align: right;">{{ __('filament-horizon::horizon.widgets.workers.processes') }}</th>
                                    <th style="padding: 0.5rem 0.75rem; text-align: right;">{{ __('filament-horizon::horizon.widgets.workers.balancing') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($worker->supervisors as $supervisor)
                                    <tr>
                                        <td style="padding: 0.5rem 0.75rem; color: var(--horizon-text-primary);">
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <span class="fi-horizon-dot fi-horizon-dot-{{ $worker->status === 'running' ? 'success' : ($worker->status === 'paused' ? 'warning' : 'danger') }}"></span>
                                                {{ str_replace($worker->name . ':', '', $supervisor->name ?? $supervisor) }}
                                            </div>
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem;">{{ $supervisor->options['connection'] ?? '-' }}</td>
                                        <td style="padding: 0.5rem 0.75rem;">
                                            <span style="display: inline-flex; flex-wrap: wrap; gap: 0.25rem;">
                                                @foreach(explode(',', $supervisor->options['queue'] ?? '-') as $queue)
                                                    <code class="fi-horizon-code">{{ trim($queue) }}</code>
                                                @endforeach
                                            </span>
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: right;">
                                            {{ number_format(is_array($supervisor->processes ?? 0) ? collect($supervisor->processes)->sum() : ($supervisor->processes ?? 0)) }}
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: right;">
                                            @php $balance = $supervisor->options['balance'] ?? null; @endphp
                                            @if($balance)
                                                <span class="fi-horizon-badge fi-horizon-badge-primary">{{ ucfirst($balance) }}</span>
                                            @else
                                                <span style="color: var(--horizon-text-muted);">Disabled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="padding: 1rem; color: var(--horizon-text-muted); text-align: center;">No supervisors configured</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="fi-horizon-empty" style="padding: 2rem;">
                    <div style="display: inline-flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 9999px; background: var(--horizon-bg-code); margin-bottom: 0.75rem;">
                        <svg style="width: 1.5rem; height: 1.5rem; color: var(--horizon-text-muted);" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Z" />
                        </svg>
                    </div>
                    <p style="font-size: 0.875rem; color: var(--horizon-text-muted);">{{ __('filament-horizon::horizon.status.inactive') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
