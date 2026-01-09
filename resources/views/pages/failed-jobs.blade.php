<x-filament-panels::page>
    <div wire:poll.5s x-data @job-retry-complete.window="setTimeout(() => $wire.jobRetryComplete($event.detail.id), 5000)">
        {{-- Header with search --}}
        <div style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
            <h3 class="fi-horizon-section-title">{{ __('filament-horizon::horizon.pages.failed_jobs.title') }}</h3>
            <div style="width: 16rem;">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model.live.debounce.500ms="tagSearch" placeholder="Search Tags..." />
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Failed Jobs Table --}}
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
                                <th style="text-align: right;">{{ __('filament-horizon::horizon.columns.runtime') }}</th>
                                <th>{{ __('filament-horizon::horizon.columns.failed_at') }}</th>
                                <th style="text-align: right;">{{ __('filament-horizon::horizon.actions.retry') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <a href="{{ \Miguelenes\FilamentHorizon\Pages\FailedJobPreview::getUrl(['jobId' => $job->id]) }}" class="fi-horizon-link-danger" style="font-weight: 500; text-decoration: none;">
                                                {{ $this->getJobBaseName($job->name ?? $job->payload->displayName ?? 'Unknown') }}
                                            </a>
                                            @if($this->wasRetried($job))
                                                <span class="fi-horizon-badge" style="background: rgba(107, 114, 128, 0.1); color: var(--horizon-text-muted); font-size: 0.625rem; padding: 0.125rem 0.375rem;">Retried</span>
                                            @endif
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--horizon-text-muted); margin-top: 0.25rem;">
                                            Queue: <code class="fi-horizon-code">{{ $job->queue ?? '-' }}</code>
                                            <span style="margin-left: 0.5rem;">Attempts: {{ $job->payload->attempts ?? 0 }}</span>
                                            @php
                                                $tags = $job->payload->tags ?? [];
                                                $tags = is_array($tags) ? $tags : (is_object($tags) ? (array) $tags : []);
                                            @endphp
                                            @if(!empty($tags))
                                                <span style="margin-left: 0.5rem;">Tags: {{ implode(', ', $tags) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        @if(isset($job->failed_at) && isset($job->reserved_at))
                                            {{ number_format($job->failed_at - $job->reserved_at, 2) }}s
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $this->formatTimestamp($job->failed_at ?? null) }}</td>
                                    <td style="text-align: right;">
                                        @if(!$this->hasCompleted($job))
                                            <button wire:click="retryJob('{{ $job->id }}')" style="padding: 0.375rem; border-radius: 0.375rem; border: none; cursor: pointer; background: rgba(251, 191, 36, 0.1); color: rgb(217, 119, 6); {{ in_array($job->id, $retryingJobs) ? 'opacity: 0.5;' : '' }}" {{ in_array($job->id, $retryingJobs) ? 'disabled' : '' }}>
                                                <svg style="width: 1rem; height: 1rem; {{ in_array($job->id, $retryingJobs) ? 'animation: spin 1s linear infinite;' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                </svg>
                                            </button>
                                        @endif
                                    </td>
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
                    {{ __('filament-horizon::horizon.messages.no_failed_jobs') }}
                </div>
            @endif
        </div>
    </div>

    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</x-filament-panels::page>
