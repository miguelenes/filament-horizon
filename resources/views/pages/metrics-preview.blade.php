<x-filament-panels::page>
    @php
        $info = $this->getMetricInfo();
        $chartData = $this->getChartData();
    @endphp

    <div wire:poll.10s>
        {{-- Metric Info --}}
        <div class="fi-horizon-card" style="margin-bottom: 1.5rem;">
            <div class="fi-horizon-card-header">
                <h3 class="fi-horizon-section-title">
                    @if($type === 'jobs')
                        {{ $this->getJobBaseName($metricSlug) }}
                    @else
                        Queue: {{ $metricSlug }}
                    @endif
                </h3>
            </div>
            <div class="fi-horizon-card-body">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                    @if($type === 'jobs')
                        <div>
                            <div class="fi-horizon-detail-label">Full Class Name</div>
                            <div class="fi-horizon-detail-value" style="font-family: monospace; word-break: break-all; font-size: 0.75rem;">{{ $metricSlug }}</div>
                        </div>
                    @endif
                    <div>
                        <div class="fi-horizon-detail-label">{{ __('filament-horizon::horizon.columns.throughput') }}</div>
                        <div class="fi-horizon-stat-value">{{ number_format($info['throughput'] ?? 0) }}</div>
                    </div>
                    <div>
                        <div class="fi-horizon-detail-label">Average {{ __('filament-horizon::horizon.columns.runtime') }}</div>
                        <div class="fi-horizon-stat-value">{{ $this->formatRuntime($info['runtime'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts --}}
        @if(!empty($chartData['labels']))
            <div class="fi-horizon-grid-2" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="fi-horizon-card">
                    <div class="fi-horizon-card-header">
                        <h3 class="fi-horizon-section-title">Throughput Over Time</h3>
                    </div>
                    <div class="fi-horizon-card-body">
                        <div
                            x-data="{
                                chart: null,
                                init() { this.renderChart(); },
                                renderChart() {
                                    const ctx = this.$refs.throughputChart.getContext('2d');
                                    const isDark = document.documentElement.classList.contains('dark');
                                    const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
                                    if (this.chart) { this.chart.destroy(); }
                                    this.chart = new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels: {{ json_encode($chartData['labels']) }},
                                            datasets: [{
                                                label: 'Throughput',
                                                data: {{ json_encode($chartData['throughput']) }},
                                                borderColor: 'rgb(217, 119, 6)',
                                                backgroundColor: 'rgba(217, 119, 6, 0.1)',
                                                fill: true,
                                                tension: 0.4
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: { legend: { display: false } },
                                            scales: { y: { beginAtZero: true, grid: { color: gridColor } }, x: { grid: { color: gridColor } } }
                                        }
                                    });
                                }
                            }"
                            wire:ignore
                        >
                            <div style="height: 16rem;"><canvas x-ref="throughputChart"></canvas></div>
                        </div>
                    </div>
                </div>

                <div class="fi-horizon-card">
                    <div class="fi-horizon-card-header">
                        <h3 class="fi-horizon-section-title">Runtime Over Time (seconds)</h3>
                    </div>
                    <div class="fi-horizon-card-body">
                        <div
                            x-data="{
                                chart: null,
                                init() { this.renderChart(); },
                                renderChart() {
                                    const ctx = this.$refs.runtimeChart.getContext('2d');
                                    const isDark = document.documentElement.classList.contains('dark');
                                    const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
                                    if (this.chart) { this.chart.destroy(); }
                                    this.chart = new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels: {{ json_encode($chartData['labels']) }},
                                            datasets: [{
                                                label: 'Runtime (s)',
                                                data: {{ json_encode($chartData['runtime']) }},
                                                borderColor: 'rgb(34, 197, 94)',
                                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                                fill: true,
                                                tension: 0.4
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: { legend: { display: false } },
                                            scales: { y: { beginAtZero: true, grid: { color: gridColor } }, x: { grid: { color: gridColor } } }
                                        }
                                    });
                                }
                            }"
                            wire:ignore
                        >
                            <div style="height: 16rem;"><canvas x-ref="runtimeChart"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Snapshot Data --}}
            <div class="fi-horizon-card">
                <details>
                    <summary class="fi-horizon-card-header" style="cursor: pointer; list-style: none;">
                        <span class="fi-horizon-section-title">Snapshot Data</span>
                    </summary>
                    <div style="overflow-x: auto;">
                        <table class="fi-horizon-table">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--horizon-border);">
                                    <th>Time</th>
                                    <th style="text-align: right;">Throughput</th>
                                    <th style="text-align: right;">Runtime</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chartData['labels'] as $index => $label)
                                    <tr>
                                        <td style="color: var(--horizon-text-primary);">{{ $label }}</td>
                                        <td style="text-align: right;">{{ $chartData['throughput'][$index] ?? 0 }}</td>
                                        <td style="text-align: right;">{{ $chartData['runtime'][$index] ?? 0 }}s</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        @else
            <div class="fi-horizon-card fi-horizon-empty">
                No snapshot data available yet.
            </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
</x-filament-panels::page>
