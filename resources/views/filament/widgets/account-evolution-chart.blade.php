@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        @if ($filters = $this->getFilters())
            <x-slot name="headerEnd">
                <x-filament::input.wrapper
                    inline-prefix
                    wire:target="filter"
                    class="w-max sm:-my-2"
                >
                    <x-filament::input.select
                        inline-prefix
                        wire:model.live="filter"
                    >
                        @foreach ($filters as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-slot>
        @endif

        <div 
            wire:ignore 
            style="height: 400px; min-height: 400px; width: 100%; position: relative;"
        >
            <canvas
                x-data="{
                    chart: null,
                }"
                x-init="
                    const initChart = () => {
                        if (typeof Chart === 'undefined') {
                            setTimeout(initChart, 100);
                            return;
                        }
                        const config = {
                            type: @js($this->getType()),
                            data: @js($this->getData()),
                            options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 20,
                                    bottom: 20,
                                }
                            },
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        display: true,
                                        color: 'rgba(255, 255, 255, 0.3)',
                                        lineWidth: 1,
                                    },
                                    title: {
                                        display: true,
                                        text: 'Solde (€)',
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        }
                                    },
                                    ticks: {
                                        stepSize: 1000,
                                        maxTicksLimit: 8,
                                        callback: function(value) { 
                                            return new Intl.NumberFormat('fr-FR', { 
                                                style: 'currency', 
                                                currency: 'EUR' 
                                            }).format(value); 
                                        }
                                    },
                                },
                                x: {
                                    grid: {
                                        display: true,
                                        color: 'rgba(255, 255, 255, 0.05)',
                                        lineWidth: 1,
                                    },
                                    title: {
                                        display: true,
                                        text: 'Période',
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        }
                                    },
                                },
                            },
                            plugins: {
                                tooltip: {
                                    enabled: true,
                                    mode: 'index',
                                    intersect: false,
                                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    borderColor: 'rgba(255, 255, 255, 0.3)',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    displayColors: true,
                                    titleFont: {
                                        size: 14,
                                        weight: 'bold'
                                    },
                                    bodyFont: {
                                        size: 13
                                    },
                                    padding: 12,
                                },
                                legend: {
                                    display: true,
                                    position: 'left',
                                    align: 'start',
                                    maxWidth: 200,
                                    labels: {
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        font: {
                                            size: 12
                                        },
                                        padding: 6,
                                        boxWidth: 8,
                                        boxHeight: 8,
                                    }
                                }
                            },
                        }
                        };
                        chart = new Chart($el, config);
                        $wire.on('updateChartData', ({ data }) => {
                            chart.data = data;
                            chart.update('resize');
                        });
                    };
                    initChart();
                "
                x-on:update-chart-data.window="
                    chart.data = $event.detail.data;
                    chart.update('resize');
                "
                style="height: 400px !important; width: 100% !important; display: block;"
            >
            </canvas>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>