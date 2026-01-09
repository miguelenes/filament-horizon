<x-filament-panels::page>
    @php
        $data = $this->getBatch();
        $batch = $data['batch'];
        $failedJobs = $data['failedJobs'];
    @endphp

    @if($batch)
        @php
            $progress = $this->calculateProgress($batch);
            $hasFailed = ($batch->failedJobs ?? 0) > 0;
            $isPending = ($batch->pendingJobs ?? 0) > 0;
            $isFinished = !$isPending && !$hasFailed && ($batch->totalJobs ?? 0) > 0;
            $progressColor = $hasFailed ? 'rgb(239, 68, 68)' : ($isFinished ? 'rgb(34, 197, 94)' : 'rgb(245, 158, 11)');
        @endphp

        <div wire:poll.5s>
            {{-- Batch Details --}}
            <div class="fi-horizon-card" style="margin-bottom: 1.5rem;">
                <div class="fi-horizon-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                    <h3 class="fi-horizon-section-title">{{ $batch->name ?? 'Unnamed Batch' }}</h3>
                    @if($hasFailed)
                        <x-filament::button wire:click="retryBatch" :disabled="$isRetrying" color="primary" size="sm" icon="heroicon-o-arrow-path">
                            {{ __('filament-horizon::horizon.actions.retry') }}
                        </x-filament::button>
                    @endif
                </div>
                <div class="fi-horizon-card-body">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                        <div>
                            <div class="fi-horizon-detail-label">Batch ID</div>
                            <div class="fi-horizon-detail-value" style="font-family: monospace;">{{ $batch->id }}</div>
                        </div>
                        <div>
                            <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.status') }}</div>
                            <div style="margin-top: 0.25rem;">
                                @php
                                    if ($batch->cancelledAt) {
                                        $statusClass = '';
                                        $statusText = 'Cancelled';
                                    } elseif ($hasFailed) {
                                        $statusClass = 'fi-horizon-badge-danger';
                                        $statusText = 'Has Failures';
                                    } elseif ($isPending) {
                                        $statusClass = 'fi-horizon-badge-warning';
                                        $statusText = 'Processing';
                                    } elseif ($isFinished) {
                                        $statusClass = 'fi-horizon-badge-success';
                                        $statusText = 'Completed';
                                    } else {
                                        $statusClass = '';
                                        $statusText = 'Unknown';
                                    }
                                @endphp
                                <span class="fi-horizon-badge {{ $statusClass }}" style="{{ !$statusClass ? 'background: rgba(107, 114, 128, 0.1); color: var(--horizon-text-muted);' : '' }}">{{ $statusText }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="fi-horizon-detail-label" style="margin-bottom: 0.5rem;">{{ __('filament-horizon::horizon.columns.progress') }}</div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="flex: 1; height: 0.5rem; background: var(--horizon-bg-code); border-radius: 9999px; overflow: hidden;">
                                    <div style="height: 100%; width: {{ $progress }}%; background: {{ $progressColor }}; transition: width 0.5s;"></div>
                                </div>
                                <span class="fi-horizon-detail-value" style="font-weight: 500;">{{ $progress }}%</span>
                            </div>
                        </div>
                        <div>
                            <div class="fi-horizon-detail-label">Total Jobs</div>
                            <div class="fi-horizon-detail-value">{{ number_format($batch->totalJobs ?? 0) }}</div>
                        </div>
                        <div>
                            <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.pending_jobs') }}</div>
                            <div class="fi-horizon-detail-value">{{ number_format($batch->pendingJobs ?? 0) }}</div>
                        </div>
                        <div>
                            <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.failed_jobs') }}</div>
                            <div class="fi-horizon-detail-value" style="{{ $hasFailed ? 'color: rgb(239, 68, 68); font-weight: 500;' : '' }}">{{ number_format($batch->failedJobs ?? 0) }}</div>
                        </div>
                        <div>
                            <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.created_at') }}</div>
                            <div class="fi-horizon-detail-value">{{ $this->formatTimestamp($batch->createdAt ?? null) }}</div>
                        </div>
                        @if($batch->finishedAt)
                            <div>
                                <div class="fi-horizon-detail-label">Finished At</div>
                                <div class="fi-horizon-detail-value">{{ $this->formatTimestamp($batch->finishedAt) }}</div>
                            </div>
                        @endif
                        @if($batch->cancelledAt)
                            <div>
                                <div class="fi-horizon-detail-label">Cancelled At</div>
                                <div class="fi-horizon-detail-value">{{ $this->formatTimestamp($batch->cancelledAt) }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Failed Jobs --}}
            @if($failedJobs && $failedJobs->isNotEmpty())
                <div class="fi-horizon-card" style="margin-bottom: 1.5rem;">
                    <div class="fi-horizon-card-header">
                        <h3 class="fi-horizon-section-title" style="color: rgb(239, 68, 68);">Failed Jobs</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="fi-horizon-table">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--horizon-border);">
                                    <th>{{ __('filament-horizon::horizon.columns.job') }}</th>
                                    <th>Job ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($failedJobs as $job)
                                    @php $payload = is_string($job->payload) ? json_decode($job->payload) : $job->payload; @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ \Miguelenes\FilamentHorizon\Pages\FailedJobPreview::getUrl(['jobId' => $job->id]) }}" class="fi-horizon-link">
                                                {{ $this->getJobBaseName($job->name ?? $payload->displayName ?? 'Unknown') }}
                                            </a>
                                        </td>
                                        <td style="font-family: monospace;">{{ \Illuminate\Support\Str::limit($job->id, 20) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Options --}}
            @if($batch->options ?? null)
                <div class="fi-horizon-card">
                    <details>
                        <summary class="fi-horizon-card-header" style="cursor: pointer; list-style: none;">
                            <span class="fi-horizon-section-title">Options</span>
                        </summary>
                        <div class="fi-horizon-card-body">
                            <pre class="fi-horizon-pre"><code>{{ json_encode($batch->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        </div>
                    </details>
                </div>
            @endif
        </div>
    @else
        <div class="fi-horizon-card fi-horizon-empty">
            Batch not found.
        </div>
    @endif
</x-filament-panels::page>
