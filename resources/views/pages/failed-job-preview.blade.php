<x-filament-panels::page>
    @php $job = $this->getJob(); @endphp

    @if($job)
        {{-- Job Details --}}
        <div class="fi-horizon-card" style="margin-bottom: 1.5rem;">
            <div class="fi-horizon-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                <h3 class="fi-horizon-section-title">
                    {{ $this->getJobBaseName($job->name ?? $job->payload->displayName ?? 'Unknown') }}
                </h3>
                @if(!$this->hasCompleted($job))
                    <x-filament::button wire:click="retryJob" :disabled="$isRetrying" color="primary" size="sm" icon="heroicon-o-arrow-path">
                        {{ __('filament-horizon::horizon.actions.retry') }}
                    </x-filament::button>
                @endif
            </div>
            <div class="fi-horizon-card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                    <div>
                        <div class="fi-horizon-detail-label">Job ID</div>
                        <div class="fi-horizon-detail-value" style="font-family: monospace;">{{ $job->id }}</div>
                    </div>
                    <div>
                        <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.queue') }}</div>
                        <div class="fi-horizon-detail-value">{{ $job->queue ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.status') }}</div>
                        <div style="margin-top: 0.25rem;">
                            <span class="fi-horizon-badge fi-horizon-badge-danger">Failed</span>
                        </div>
                    </div>
                    <div>
                        <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.attempts') }}</div>
                        <div class="fi-horizon-detail-value">{{ $job->payload->attempts ?? 0 }}</div>
                    </div>
                    @if(isset($job->reserved_at))
                        <div>
                            <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.reserved_at') }}</div>
                            <div class="fi-horizon-detail-value">{{ $this->formatTimestamp($job->reserved_at) }}</div>
                        </div>
                    @endif
                    @if(isset($job->failed_at))
                        <div>
                            <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.failed_at') }}</div>
                            <div class="fi-horizon-detail-value">{{ $this->formatTimestamp($job->failed_at) }}</div>
                        </div>
                    @endif
                    @if(isset($job->failed_at) && isset($job->reserved_at))
                        <div>
                            <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.runtime') }}</div>
                            <div class="fi-horizon-detail-value">{{ number_format($job->failed_at - $job->reserved_at, 2) }}s</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Retry History --}}
        @if(isset($job->retried_by) && $job->retried_by instanceof \Illuminate\Support\Collection && $job->retried_by->isNotEmpty())
            <div class="fi-horizon-card" style="margin-bottom: 1.5rem;">
                <div class="fi-horizon-card-header">
                    <h3 class="fi-horizon-section-title">Retry History</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="fi-horizon-table">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--horizon-border);">
                                <th>Retry ID</th>
                                <th>Status</th>
                                <th>Retried At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($job->retried_by as $retry)
                                @php $status = $retry->status ?? 'unknown'; @endphp
                                <tr>
                                    <td style="font-family: monospace; color: var(--horizon-text-primary);">{{ $retry->id ?? '-' }}</td>
                                    <td>
                                        <span class="fi-horizon-badge fi-horizon-badge-{{ $status === 'completed' ? 'success' : ($status === 'failed' ? 'danger' : ($status === 'pending' ? 'warning' : 'primary')) }}">{{ ucfirst($status) }}</span>
                                    </td>
                                    <td>{{ isset($retry->retried_at) ? \Carbon\Carbon::createFromTimestamp($retry->retried_at)->format('Y-m-d H:i:s') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Tags --}}
        @php
            $tags = $job->payload->tags ?? [];
            $tags = is_array($tags) ? $tags : (is_object($tags) ? (array) $tags : []);
        @endphp
        @if(!empty($tags))
            <div class="fi-horizon-card" style="margin-bottom: 1.5rem;">
                <div class="fi-horizon-card-header">
                    <h3 class="fi-horizon-section-title">{{ __('filament-horizon::horizon.columns.tags') }}</h3>
                </div>
                <div class="fi-horizon-card-body" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @foreach($tags as $tag)
                        <span class="fi-horizon-badge fi-horizon-badge-primary">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Exception --}}
        @if(!empty($job->exception))
            <div class="fi-horizon-card" style="margin-bottom: 1.5rem;">
                <div class="fi-horizon-card-header">
                    <h3 class="fi-horizon-section-title" style="color: rgb(239, 68, 68);">Exception</h3>
                </div>
                <div class="fi-horizon-card-body">
                    <pre class="fi-horizon-pre fi-horizon-pre-error">{{ $job->exception }}</pre>
                </div>
            </div>
        @endif

        {{-- Payload --}}
        <div class="fi-horizon-card" style="margin-bottom: 1.5rem;">
            <details>
                <summary class="fi-horizon-card-header" style="cursor: pointer; list-style: none;">
                    <span class="fi-horizon-section-title">Payload</span>
                </summary>
                <div class="fi-horizon-card-body">
                    <pre class="fi-horizon-pre"><code>{{ json_encode($job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </div>
            </details>
        </div>

        {{-- Context --}}
        @if(!empty($job->context))
            <div class="fi-horizon-card">
                <details>
                    <summary class="fi-horizon-card-header" style="cursor: pointer; list-style: none;">
                        <span class="fi-horizon-section-title">Context</span>
                    </summary>
                    <div class="fi-horizon-card-body">
                        <pre class="fi-horizon-pre"><code>{{ json_encode($job->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                    </div>
                </details>
            </div>
        @endif
    @else
        <div class="fi-horizon-card fi-horizon-empty">
            Job not found.
        </div>
    @endif
</x-filament-panels::page>
