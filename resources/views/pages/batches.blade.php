<x-filament-panels::page>
    <div wire:poll.5s>
        {{-- Batches Table --}}
        <div class="fi-horizon-card">
            @php
                $data = $this->getBatches();
                $batches = $data['batches'] ?? [];
            @endphp

            @if(!empty($batches))
                <div style="overflow-x: auto;">
                    <table class="fi-horizon-table">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--horizon-border);">
                                <th>{{ __('filament-horizon::horizon.columns.name') }}</th>
                                <th style="width: 12rem;">{{ __('filament-horizon::horizon.columns.progress') }}</th>
                                <th style="text-align: right;">{{ __('filament-horizon::horizon.columns.pending_jobs') }}</th>
                                <th style="text-align: right;">{{ __('filament-horizon::horizon.columns.failed_jobs') }}</th>
                                <th>{{ __('filament-horizon::horizon.columns.created_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batches as $batch)
                                @php
                                    $progress = $this->calculateProgress($batch);
                                    $isPending = $batch->pendingJobs > 0;
                                    $hasFailed = $batch->failedJobs > 0;
                                    $isFinished = !$isPending && !$hasFailed && $batch->totalJobs > 0;
                                    $progressColor = $hasFailed ? 'rgb(239, 68, 68)' : ($isFinished ? 'rgb(34, 197, 94)' : 'rgb(245, 158, 11)');
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ \Miguelenes\FilamentHorizon\Pages\BatchPreview::getUrl(['batchId' => $batch->id]) }}" class="fi-horizon-link">
                                            {{ $batch->name ?? 'Unnamed Batch' }}
                                        </a>
                                        <div style="font-size: 0.75rem; color: var(--horizon-text-muted); margin-top: 0.25rem; font-family: monospace;">
                                            {{ \Illuminate\Support\Str::limit($batch->id, 12) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="flex: 1; height: 0.5rem; background: var(--horizon-bg-code); border-radius: 9999px; overflow: hidden;">
                                                <div style="height: 100%; width: {{ $progress }}%; background: {{ $progressColor }}; transition: width 0.5s;"></div>
                                            </div>
                                            <span style="font-size: 0.75rem; color: var(--horizon-text-muted); width: 2.5rem; text-align: right;">{{ $progress }}%</span>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">{{ number_format($batch->pendingJobs ?? 0) }}</td>
                                    <td style="text-align: right; {{ $hasFailed ? 'color: rgb(239, 68, 68); font-weight: 500;' : '' }}">
                                        {{ number_format($batch->failedJobs ?? 0) }}
                                    </td>
                                    <td>{{ $this->formatTimestamp($batch->createdAt ?? null) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(count($batches) >= 50)
                    <div style="padding: 1rem; border-top: 1px solid var(--horizon-border); text-align: center;">
                        <x-filament::button wire:click="loadMore" color="gray" size="sm">Load More</x-filament::button>
                    </div>
                @endif
            @else
                <div class="fi-horizon-empty">
                    {{ __('filament-horizon::horizon.messages.no_batches') }}
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
