<x-filament-panels::page>
    @php
        $api = app(\Miguelenes\FilamentHorizon\Services\HorizonApi::class);
        $stats = $api->getStats();
        $status = $stats['status'];
        $recentPeriod = \Carbon\CarbonInterval::minutes($stats['periods']['recentJobs'] ?? 60)->cascade()->forHumans(['short' => true]);
        $failedPeriod = \Carbon\CarbonInterval::minutes($stats['periods']['failedJobs'] ?? 10080)->cascade()->forHumans(['short' => true]);
        
        $maxWait = '-';
        $maxWaitQueue = null;
        if ($stats['wait']->isNotEmpty()) {
            $waitData = $stats['wait']->first();
            $maxWait = $waitData < 60 ? $waitData . 's' : \Carbon\CarbonInterval::seconds($waitData)->cascade()->forHumans(['short' => true]);
            $maxWaitQueue = $stats['wait']->keys()->first();
            if ($maxWaitQueue) {
                $maxWaitQueue = explode(':', $maxWaitQueue)[1] ?? $maxWaitQueue;
            }
        }
        
        $workload = collect($api->getWorkload());
        $masters = collect($api->getMasters());
    @endphp

    <div wire:poll.5s>
        {{-- Status Banner --}}
        <div class="fi-horizon-card" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem; padding: 1rem; {{ $status === 'running' ? 'background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.2);' : ($status === 'paused' ? 'background: rgba(234, 179, 8, 0.1); border-color: rgba(234, 179, 8, 0.2);' : 'background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2);') }}">
            <div style="flex-shrink: 0; width: 3rem; height: 3rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; {{ $status === 'running' ? 'background: rgba(34, 197, 94, 0.2);' : ($status === 'paused' ? 'background: rgba(234, 179, 8, 0.2);' : 'background: rgba(239, 68, 68, 0.2);') }}">
                @if($status === 'running')
                    <svg style="width: 1.5rem; height: 1.5rem; color: rgb(34, 197, 94);" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                @elseif($status === 'paused')
                    <svg style="width: 1.5rem; height: 1.5rem; color: rgb(234, 179, 8);" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                    </svg>
                @else
                    <svg style="width: 1.5rem; height: 1.5rem; color: rgb(239, 68, 68);" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                @endif
            </div>
            <div style="flex: 1;">
                <h3 style="font-size: 1.125rem; font-weight: 600; margin: 0;" class="fi-horizon-status-{{ $status === 'running' ? 'running' : ($status === 'paused' ? 'paused' : 'inactive') }}">
                    Horizon is {{ __('filament-horizon::horizon.status.' . $status) }}
                </h3>
                <p style="font-size: 0.875rem; margin: 0.25rem 0 0 0; color: var(--horizon-text-muted);">
                    {{ number_format($stats['processes'] ?? 0) }} processes running
                    @if($stats['pausedMasters'] > 0)
                        · {{ $stats['pausedMasters'] }} paused
                    @endif
                </p>
            </div>
            <div style="text-align: right;">
                <div class="fi-horizon-stat-value">{{ number_format($stats['jobsPerMinute'] ?? 0) }}</div>
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--horizon-text-muted);">Jobs/min</div>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="fi-horizon-grid-4" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
            {{-- Recent Jobs --}}
            <div class="fi-horizon-card" style="padding: 1rem;">
                <div class="fi-horizon-stat-label">Jobs Past {{ $recentPeriod }}</div>
                <div class="fi-horizon-stat-value">{{ number_format($stats['recentJobs'] ?? 0) }}</div>
            </div>

            {{-- Failed Jobs --}}
            <div class="fi-horizon-card" style="padding: 1rem;">
                <div class="fi-horizon-stat-label">Failed Past {{ $failedPeriod }}</div>
                <div class="fi-horizon-stat-value" style="{{ ($stats['failedJobs'] ?? 0) > 0 ? 'color: rgb(239, 68, 68);' : '' }}">{{ number_format($stats['failedJobs'] ?? 0) }}</div>
            </div>

            {{-- Max Wait --}}
            <div class="fi-horizon-card" style="padding: 1rem;">
                <div class="fi-horizon-stat-label">Max Wait</div>
                <div class="fi-horizon-stat-value">{{ $maxWait }}</div>
                @if($maxWaitQueue)
                    <div style="font-size: 0.75rem; color: var(--horizon-text-muted); margin-top: 0.25rem;">{{ $maxWaitQueue }}</div>
                @endif
            </div>

            {{-- Total Queues --}}
            <div class="fi-horizon-card" style="padding: 1rem;">
                <div class="fi-horizon-stat-label">Total Queues</div>
                <div class="fi-horizon-stat-value">{{ $stats['totalQueues'] ?? 0 }}</div>
            </div>
        </div>

        {{-- Workload & Workers Grid --}}
        <div class="fi-horizon-grid-2" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            {{-- Workload Section --}}
            <div class="fi-horizon-card">
                <div class="fi-horizon-card-header">
                    <h3 class="fi-horizon-section-title">Current Workload</h3>
                </div>
                <div class="fi-horizon-card-body">
                    @if($workload->isNotEmpty())
                        <table class="fi-horizon-table">
                            <thead>
                                <tr>
                                    <th style="padding: 0.5rem 0; text-align: left;">Queue</th>
                                    <th style="padding: 0.5rem 0; text-align: right;">Jobs</th>
                                    <th style="padding: 0.5rem 0; text-align: right;">Procs</th>
                                    <th style="padding: 0.5rem 0; text-align: right;">Wait</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workload as $queue)
                                    <tr>
                                        <td style="padding: 0.5rem 0;">
                                            <code class="fi-horizon-code">{{ $queue['name'] ?? '-' }}</code>
                                        </td>
                                        <td style="padding: 0.5rem 0; text-align: right;">{{ number_format($queue['length'] ?? 0) }}</td>
                                        <td style="padding: 0.5rem 0; text-align: right;">{{ number_format($queue['processes'] ?? 0) }}</td>
                                        <td style="padding: 0.5rem 0; text-align: right;">
                                            @php
                                                $wait = $queue['wait'] ?? 0;
                                                $waitFormatted = $wait < 60 ? $wait . 's' : \Carbon\CarbonInterval::seconds($wait)->cascade()->forHumans(['short' => true]);
                                            @endphp
                                            {{ $waitFormatted }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="fi-horizon-empty" style="padding: 2rem 0;">
                            <svg style="width: 2rem; height: 2rem; margin: 0 auto 0.5rem; opacity: 0.5;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                            </svg>
                            No queues active
                        </div>
                    @endif
                </div>
            </div>

            {{-- Workers Section --}}
            <div class="fi-horizon-card">
                <div class="fi-horizon-card-header">
                    <h3 class="fi-horizon-section-title">Workers</h3>
                </div>
                <div class="fi-horizon-card-body">
                    @if($masters->isNotEmpty())
                        @foreach($masters as $master)
                            <div style="{{ !$loop->last ? 'margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--horizon-border-subtle);' : '' }}">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <span class="fi-horizon-dot fi-horizon-dot-{{ ($master->status ?? '') === 'running' ? 'success' : (($master->status ?? '') === 'paused' ? 'warning' : 'danger') }}"></span>
                                    <span style="font-size: 0.875rem; font-weight: 500; color: var(--horizon-text-primary);">{{ $master->name ?? 'Unknown' }}</span>
                                    <span class="fi-horizon-badge fi-horizon-badge-{{ ($master->status ?? '') === 'running' ? 'success' : (($master->status ?? '') === 'paused' ? 'warning' : 'danger') }}">{{ ucfirst($master->status ?? 'inactive') }}</span>
                                </div>
                                @if(isset($master->supervisors) && is_array($master->supervisors) && count($master->supervisors) > 0)
                                    <table class="fi-horizon-table" style="font-size: 0.875rem;">
                                        <thead>
                                            <tr>
                                                <th style="padding: 0.375rem 0; text-align: left;">Supervisor</th>
                                                <th style="padding: 0.375rem 0; text-align: left;">Queue</th>
                                                <th style="padding: 0.375rem 0; text-align: right;">Procs</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($master->supervisors as $supervisor)
                                                <tr>
                                                    <td style="padding: 0.375rem 0; color: var(--horizon-text-secondary);">
                                                        @php
                                                            $supName = is_object($supervisor) ? ($supervisor->name ?? $supervisor) : $supervisor;
                                                            $supName = str_replace(($master->name ?? '') . ':', '', $supName);
                                                        @endphp
                                                        {{ $supName }}
                                                    </td>
                                                    <td style="padding: 0.375rem 0;">
                                                        <code class="fi-horizon-code">{{ is_object($supervisor) ? ($supervisor->options['queue'] ?? '-') : '-' }}</code>
                                                    </td>
                                                    <td style="padding: 0.375rem 0; text-align: right;">
                                                        @php
                                                            $procs = is_object($supervisor) ? ($supervisor->processes ?? 0) : 0;
                                                            $procs = is_array($procs) ? collect($procs)->sum() : $procs;
                                                        @endphp
                                                        {{ $procs }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="fi-horizon-empty" style="padding: 2rem 0;">
                            <svg style="width: 2rem; height: 2rem; margin: 0 auto 0.5rem; opacity: 0.5;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3" />
                            </svg>
                            No workers active
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
