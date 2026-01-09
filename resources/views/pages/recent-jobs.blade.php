<x-filament-panels::page>
    <div wire:poll.5s>
        {{-- Header with tabs and search --}}
        <div style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; gap: 0.5rem;">
                <button
                    wire:click="setType('pending')"
                    class="{{ $type === 'pending' ? 'fi-horizon-badge fi-horizon-badge-warning' : '' }}"
                    style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; {{ $type === 'pending' ? '' : 'background: var(--horizon-bg-code); color: var(--horizon-text-muted);' }}"
                >
                    {{ __('filament-horizon::horizon.pages.jobs.pending') }}
                </button>
                <button
                    wire:click="setType('completed')"
                    class="{{ $type === 'completed' ? 'fi-horizon-badge fi-horizon-badge-success' : '' }}"
                    style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; {{ $type === 'completed' ? '' : 'background: var(--horizon-bg-code); color: var(--horizon-text-muted);' }}"
                >
                    {{ __('filament-horizon::horizon.pages.jobs.completed') }}
                </button>
                <button
                    wire:click="setType('silenced')"
                    style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; {{ $type === 'silenced' ? 'background: rgba(107, 114, 128, 0.2); color: var(--horizon-text-muted);' : 'background: var(--horizon-bg-code); color: var(--horizon-text-muted);' }}"
                >
                    {{ __('filament-horizon::horizon.pages.jobs.silenced') }}
                </button>
            </div>
            <div style="width: 16rem;">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live.debounce.500ms="tagSearch"
                        placeholder="Search Tags..."
                    />
                </x-filament::input.wrapper>
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
                                <th style="text-align: right;">{{ __('filament-horizon::horizon.columns.runtime') }}</th>
                                <th>{{ __('filament-horizon::horizon.columns.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                                <tr>
                                    <td>
                                        <a href="{{ \Miguelenes\FilamentHorizon\Pages\JobPreview::getUrl(['jobId' => $job->id]) }}" class="fi-horizon-link">
                                            {{ $this->getJobBaseName($job->name ?? $job->payload->displayName ?? 'Unknown') }}
                                        </a>
                                        <div style="font-size: 0.75rem; color: var(--horizon-text-muted); margin-top: 0.25rem;">
                                            Queue: <code class="fi-horizon-code">{{ $job->queue ?? '-' }}</code>
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
                                        @if(isset($job->completed_at) && isset($job->reserved_at))
                                            {{ number_format($job->completed_at - $job->reserved_at, 2) }}s
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($type === 'pending')
                                            <span class="fi-horizon-badge fi-horizon-badge-warning">Pending</span>
                                        @elseif($type === 'completed')
                                            <span class="fi-horizon-badge fi-horizon-badge-success">Completed</span>
                                        @else
                                            <span class="fi-horizon-badge" style="background: rgba(107, 114, 128, 0.1); color: var(--horizon-text-muted);">Silenced</span>
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
                        <x-filament::button size="sm" color="gray" wire:click="previousPage" :disabled="$page <= 1">
                            Previous
                        </x-filament::button>
                        <x-filament::button size="sm" color="gray" wire:click="nextPage" :disabled="$page >= $totalPages">
                            Next
                        </x-filament::button>
                    </div>
                </div>
            @else
                <div class="fi-horizon-empty">
                    {{ __('filament-horizon::horizon.messages.no_jobs') }}
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
