<x-filament-widgets::widget>
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush

    <x-filament::section>
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        @if ($filters = $this->getFilters())
            <x-slot name="headerEnd">
                <x-filament::input.wrapper
                    inline-prefix
                    wire:target="filter"
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
            style="height: 800px; min-height: 800px; width: 100%; position: relative;"
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
                            options: @js($this->getOptions())
                        };
                        config.options.responsive = true;
                        config.options.maintainAspectRatio = false;
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
                style="height: 800px !important; width: 100% !important; display: block;"
            >
            </canvas>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
