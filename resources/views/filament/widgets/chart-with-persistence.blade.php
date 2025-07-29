@php
    $widgetId = class_basename($this);
    $chartType = match($widgetId) {
        'AccountEvolutionChart' => 'account_evolution',
        'IncomeExpenseChart' => 'income_expense',
        default => strtolower($widgetId)
    };
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="fi-wi-chart">
            <div class="fi-wi-chart-header flex items-center justify-between">
                <h3 class="fi-wi-chart-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $this->getHeading() }}
                </h3>

                @if ($filters = $this->getFilters())
                    <select 
                        wire:model.live="filter"
                        data-chart-filter="{{ $chartType }}"
                        class="fi-select-input block w-auto rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        @foreach ($filters as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="fi-wi-chart-content mt-6">
                <canvas
                    x-data="{
                        chart: null,
                        init() {
                            $nextTick(() => {
                                this.initChart();
                            });
                            
                            // Observer les changements de données
                            $watch('$wire.data', () => {
                                this.updateChart();
                            });
                        },
                        initChart() {
                            const ctx = this.$refs.canvas.getContext('2d');
                            
                            this.chart = new Chart(ctx, {
                                type: '{{ $this->getType() }}',
                                data: @js($this->getCachedData()),
                                options: @js($this->getOptions())
                            });
                        },
                        updateChart() {
                            if (this.chart) {
                                this.chart.data = $wire.data;
                                this.chart.update('none');
                            }
                        }
                    }"
                    x-ref="canvas"
                    class="max-h-96"
                    @if ($maxHeight = $this->getMaxHeight())
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

@push('scripts')
<script>
// S'assurer que le script de persistance est chargé
if (typeof window.budgetStorage !== 'undefined') {
    // Forcer la restauration du filtre après le rendu
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            window.budgetStorage.restoreChartFilters();
        }, 100);
    });
}
</script>
@endpush