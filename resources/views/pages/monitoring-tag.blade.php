<x-filament-panels::page>
    <div wire:poll.5s x-data @job-retry-complete.window="setTimeout(() => $wire.jobRetryComplete($event.detail.id), 5000)">
        {{-- Header with tabs --}}
        <div style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <span class="fi-horizon-badge fi-horizon-badge-primary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; font-weight: 600;">{{ $tag }}</span>
            <div style="display: flex; gap: 0.5rem;">
                <button
                    wire:click="setType('jobs')"
                    class="{{ $type === 'jobs' ? 'fi-horizon-badge fi-horizon-badge-primary' : '' }}"
                    style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; {{ $type === 'jobs' ? '' : 'background: var(--horizon-bg-code); color: var(--horizon-text-muted);' }}"
                >
                    Jobs
                </button>
                <button
                    wire:click="setType('failed')"
                    class="{{ $type === 'failed' ? 'fi-horizon-badge fi-horizon-badge-danger' : '' }}"
                    style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; {{ $type === 'failed' ? '' : 'background: var(--horizon-bg-code); color: var(--horizon-text-muted);' }}"
                >
                    Failed
                </button>
            </div>
        </div>

        {{-- Jobs Table --}}
        <div class="fi-horizon-card">
            @php
                $data = $this->getJobs();
                $jobs = $data['jobs'];
                $total = $data['total'];
                $totalPages = $this->getTotalPages();
            @endphp

            @if($jobs->isNotEmpty())
                <div style="overflow-x: auto;">
                    <table class="fi-horizon-table">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--horizon-border);">
                                <th>{{ __('filament-horizon::horizon.columns.job') }}</th>
                                <th>{{ __('filament-horizon::horizon.columns.status') }}</th>
                                @if($type === 'failed')
                                    <th style="text-align: right;">{{ __('filament-horizon::horizon.actions.retry') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                                @php
                                    $previewPage = $type === 'failed'
                                        ? \Miguelenes\FilamentHorizon\Pages\FailedJobPreview::class
                                        : \Miguelenes\FilamentHorizon\Pages\JobPreview::class;
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ $previewPage::getUrl(['jobId' => $job->id]) }}" class="fi-horizon-link">
                                            {{ $this->getJobBaseName($job->name ?? $job->payload->displayName ?? 'Unknown') }}
                                        </a>
                                        <div style="font-size: 0.75rem; color: var(--horizon-text-muted); margin-top: 0.25rem;">
                                            Queue: <code class="fi-horizon-code">{{ $job->queue ?? '-' }}</code>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $status = $type === 'failed' ? 'failed' : ($job->status ?? 'unknown');
                                            $statusText = $status === 'reserved' ? 'Processing' : ucfirst($status);
                                        @endphp
                                        <span class="fi-horizon-badge fi-horizon-badge-{{ $status === 'completed' ? 'success' : ($status === 'failed' ? 'danger' : ($status === 'pending' || $status === 'reserved' ? 'warning' : 'primary')) }}">{{ $statusText }}</span>
                                    </td>
                                    @if($type === 'failed')
                                        <td style="text-align: right;">
                                            <button wire:click="retryJob('{{ $job->id }}')" style="padding: 0.375rem; border-radius: 0.375rem; border: none; cursor: pointer; background: rgba(251, 191, 36, 0.1); color: rgb(217, 119, 6); {{ in_array($job->id, $retryingJobs) ? 'opacity: 0.5;' : '' }}" {{ in_array($job->id, $retryingJobs) ? 'disabled' : '' }}>
                                                <svg style="width: 1rem; height: 1rem; {{ in_array($job->id, $retryingJobs) ? 'animation: spin 1s linear infinite;' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                </svg>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div style="padding: 1rem; border-top: 1px solid var(--horizon-border); display: flex; align-items: center; justify-content: space-between;">
                    <div style="font-size: 0.875rem; color: var(--horizon-text-muted);">
                        Page {{ $page }} of {{ $totalPages }} ({{ number_format($total) }} total)
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <x-filament::button size="sm" color="gray" wire:click="previousPage" :disabled="$page <= 1">Previous</x-filament::button>
                        <x-filament::button size="sm" color="gray" wire:click="nextPage" :disabled="$page >= $totalPages">Next</x-filament::button>
                    </div>
                </div>
            @else
                <div class="fi-horizon-empty">
                    {{ __('filament-horizon::horizon.messages.no_jobs') }}
                </div>
            @endif
        </div>
    </div>

    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</x-filament-panels::page>
