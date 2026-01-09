<x-filament-panels::page>
    @php $job = $this->getJob(); @endphp

    @if($job)
        {{-- Job Details --}}
        <div class="fi-horizon-card" style="margin-bottom: 1.5rem;">
            <div class="fi-horizon-card-header">
                <h3 class="fi-horizon-section-title">
                    {{ $this->getJobBaseName($job->name ?? $job->payload->displayName ?? 'Unknown') }}
                </h3>
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
                            @php $status = $job->status ?? 'unknown'; @endphp
                            <span class="fi-horizon-badge fi-horizon-badge-{{ $status === 'completed' ? 'success' : ($status === 'failed' ? 'danger' : ($status === 'pending' || $status === 'reserved' ? 'warning' : 'primary')) }}">
                                {{ $status === 'reserved' ? 'Processing' : ucfirst($status) }}
                            </span>
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
                    @if(isset($job->completed_at))
                        <div>
                            <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.completed_at') }}</div>
                            <div class="fi-horizon-detail-value">{{ $this->formatTimestamp($job->completed_at) }}</div>
                        </div>
                    @endif
                    @if(isset($job->completed_at) && isset($job->reserved_at))
                        <div>
                            <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.runtime') }}</div>
                            <div class="fi-horizon-detail-value">{{ number_format($job->completed_at - $job->reserved_at, 2) }}s</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

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

        {{-- Payload --}}
        <div class="fi-horizon-card">
            <details>
                <summary class="fi-horizon-card-header" style="cursor: pointer; list-style: none;">
                    <span class="fi-horizon-section-title">Payload</span>
                </summary>
                <div class="fi-horizon-card-body">
                    <pre class="fi-horizon-pre"><code>{{ json_encode($job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </div>
            </details>
        </div>
    @else
        <div class="fi-horizon-card fi-horizon-empty">
            Job not found.
        </div>
    @endif
</x-filament-panels::page>
