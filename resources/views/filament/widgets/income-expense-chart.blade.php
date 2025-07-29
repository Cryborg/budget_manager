@php
    use Filament\Support\Facades\FilamentView;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $filters = $this->getFilters();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section :description="$description" :heading="$heading">
        @if ($filters)
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
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
        >
            <div
                @if (FilamentView::hasSpaMode())
                    x-load="visible"
                @else
                    x-load
                @endif
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            options: @js($this->getOptions()),
                            type: @js($this->getType()),
                        })"
                @class([
                    match ($color) {
                        'gray' => null,
                        default => 'fi-color-custom',
                    },
                    is_string($color) ? "fi-color-{$color}" : null,
                ])
                style="height: 480px; min-height: 480px;"
                x-init="() => {
                    let currentData = @js($this->getCachedData());
                    
                    // Fonction pour créer/obtenir le tooltip HTML
                    const getOrCreateTooltip = (chart) => {
                        let tooltipEl = chart.canvas.parentNode.querySelector('.custom-tooltip');
                        if (!tooltipEl) {
                            tooltipEl = document.createElement('div');
                            tooltipEl.className = 'custom-tooltip';
                            tooltipEl.style.cssText = `
                                background: rgba(0, 0, 0, 0.9);
                                border-radius: 8px;
                                color: white;
                                opacity: 0;
                                pointer-events: none;
                                position: absolute;
                                transform: translate(-50%, -100%);
                                transition: opacity 0.2s;
                                z-index: 1000;
                                font-size: 13px;
                                padding: 14px 16px;
                                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                                border: 1px solid rgba(255, 255, 255, 0.1);
                                min-width: 280px;
                                max-width: 500px;
                                white-space: nowrap;
                                width: max-content;
                            `;
                            chart.canvas.parentNode.appendChild(tooltipEl);
                        }
                        return tooltipEl;
                    };
                    
                    // Handler du tooltip externe selon la doc Chart.js
                    const externalTooltipHandler = (context) => {
                        const { chart, tooltip } = context;
                        const tooltipEl = getOrCreateTooltip(chart);
                        
                        // Masquer si pas de tooltip
                        if (tooltip.opacity === 0) {
                            tooltipEl.style.opacity = '0';
                            return;
                        }
                        
                        // Construire le contenu HTML
                        let innerHTML = '';
                        
                        // Titre
                        if (tooltip.title && tooltip.title.length > 0) {
                            innerHTML += `<div style='font-weight: bold; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.2);'>${tooltip.title[0]}</div>`;
                        }
                        
                        // Datasets (Revenus/Dépenses)
                        tooltip.dataPoints.forEach((dataPoint, index) => {
                            const datasetLabel = dataPoint.dataset.label;
                            const value = dataPoint.formattedValue;
                            const color = dataPoint.dataset.borderColor;
                            
                            // Ajouter plus d'espace avant les Dépenses (dataset index 1)
                            const marginTop = index > 0 ? '16px' : '0px';
                            
                            innerHTML += `
                                <div style='display: flex; align-items: center; gap: 8px; margin-bottom: 8px; margin-top: ${marginTop};'>
                                    <span style='width: 12px; height: 12px; background-color: ${color}; border-radius: 50%; flex-shrink: 0;'></span>
                                    <span style='font-weight: 500; white-space: nowrap;'>${datasetLabel}</span>
                                    <span style='margin-left: auto; font-weight: 600; white-space: nowrap;'>${value}&nbsp;€</span>
                                </div>
                            `;
                            
                            // Ajouter les détails spécifiques
                            const dataIndex = dataPoint.dataIndex;
                            const datasetIndex = dataPoint.datasetIndex;
                            
                            let details = [];
                            if (datasetIndex === 0 && currentData.incomeDetails && currentData.incomeDetails.hasOwnProperty(dataIndex)) {
                                details = currentData.incomeDetails[dataIndex];
                            } else if (datasetIndex === 1 && currentData.expenseDetails && currentData.expenseDetails.hasOwnProperty(dataIndex)) {
                                details = currentData.expenseDetails[dataIndex];
                            }
                            
                            if (details.length > 0) {
                                innerHTML += `<div style='margin-top: 4px; padding-top: 4px; border-top: 1px solid rgba(255,255,255,0.1); line-height: 1.4; margin-left: 20px; white-space: normal;'>`;
                                
                                details.forEach(detail => {
                                    const formatNumber = (num) => new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num).replace(',', ',').replace(/\\s/g, ' ') + '&nbsp;€';
                                    
                                    // Fonction helper pour afficher le nom avec le compte
                                    const formatNameWithAccount = (name, account, color = '#9ca3af') => {
                                        const accountDisplay = account ? ` <span style='color: ${color}; font-size: 10px;'>${account}</span>` : '';
                                        return name + accountDisplay;
                                    };
                                    
                                    switch(detail.type) {
                                        case 'new':
                                            innerHTML += `
                                                <div style='display: flex; align-items: center; gap: 10px; margin-bottom: 8px; line-height: 1.5;'>
                                                    <span style='width: 8px; height: 8px; background-color: #22c55e; border-radius: 50%; flex-shrink: 0;'></span>
                                                    <span style='font-weight: 500; white-space: nowrap;'>${formatNameWithAccount(detail.name, detail.account)}</span>
                                                    <span style='margin-left: auto; font-weight: 600; white-space: nowrap;'>${formatNumber(detail.amount)}</span>
                                                </div>
                                            `;
                                            break;
                                        case 'modified':
                                            const diffColor = detail.diff > 0 ? '#22c55e' : '#ef4444';
                                            const diffSign = detail.diff > 0 ? '+' : '';
                                            innerHTML += `
                                                <div style='display: flex; align-items: center; gap: 10px; margin-bottom: 8px; line-height: 1.5;'>
                                                    <span style='width: 8px; height: 8px; background-color: #eab308; border-radius: 50%; flex-shrink: 0;'></span>
                                                    <span style='font-weight: 500; white-space: nowrap;'>${formatNameWithAccount(detail.name, detail.account)}</span>
                                                    <span style='margin-left: auto; font-weight: 600; white-space: nowrap;'>${formatNumber(detail.amount)}</span>
                                                    <span style='color: ${diffColor}; font-size: 11px; white-space: nowrap;'>(${diffSign}${formatNumber(detail.diff)})</span>
                                                </div>
                                            `;
                                            break;
                                        case 'temporary':
                                            innerHTML += `
                                                <div style='margin-bottom: 8px;'>
                                                    <div style='display: flex; align-items: center; gap: 10px; line-height: 1.5;'>
                                                        <span style='width: 8px; height: 8px; background-color: #f97316; border-radius: 50%; flex-shrink: 0;'></span>
                                                        <span style='font-weight: 500; white-space: nowrap;'>${formatNameWithAccount(detail.name, detail.account)}</span>
                                                        <span style='margin-left: auto; font-weight: 600; white-space: nowrap;'>${formatNumber(detail.amount)}</span>
                                                    </div>
                                                    <div style='margin-left: 14px; font-size: 11px; color: #d1d5db; margin-top: 3px; line-height: 1.6;'>
                                                        <div style='margin-bottom: 2px; white-space: nowrap;'>📅 Fin le : ${detail.endDate}</div>
                                                        <div style='white-space: nowrap;'>⏳ Échéances restantes : ${detail.remainingPayments}</div>
                                                    </div>
                                                </div>
                                            `;
                                            break;
                                        case 'terminated':
                                            innerHTML += `
                                                <div style='display: flex; align-items: center; gap: 10px; margin-bottom: 8px; line-height: 1.5;'>
                                                    <span style='width: 8px; height: 8px; background-color: #6b7280; border-radius: 50%; flex-shrink: 0;'></span>
                                                    <span style='font-weight: 500; color: #9ca3af; text-decoration: line-through; white-space: nowrap;'>${formatNameWithAccount(detail.name, detail.account, '#6b7280')}</span>
                                                    <span style='margin-left: auto; color: #6b7280; font-size: 12px; white-space: nowrap;'>${detail.terminatedLabel}</span>
                                                </div>
                                            `;
                                            break;
                                    }
                                });
                                
                                innerHTML += `</div>`;
                            }
                        });
                        
                        tooltipEl.innerHTML = innerHTML;
                        
                        // Positionner le tooltip
                        const { offsetLeft: positionX, offsetTop: positionY } = chart.canvas;
                        tooltipEl.style.opacity = '1';
                        tooltipEl.style.left = positionX + tooltip.caretX + 'px';
                        tooltipEl.style.top = positionY + tooltip.caretY + 'px';
                    };
                    
                    // Attendre que le graphique soit initialisé
                    setTimeout(() => {
                        const chartInstance = Alpine.$data($el).getChart();
                        if (chartInstance) {
                            // Configurer le tooltip externe
                            chartInstance.options.plugins.tooltip.external = externalTooltipHandler;
                            chartInstance.update('none');
                            
                            // Écouter les changements de données
                            $wire.on('updateChartData', ({ data: newData }) => {
                                currentData = newData;
                            });
                        }
                    }, 200);
                }"
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight = $this->getMaxHeight())
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>

                <span
                    x-ref="backgroundColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-100 dark:text-gray-800',
                            default => 'text-custom-50 dark:text-custom-400/10',
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [50, 400],
                            alias: 'widgets::chart-widget.background',
                        ) => $color !== 'gray',
                    ])
                ></span>

                <span
                    x-ref="borderColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-400',
                            default => 'text-custom-500 dark:text-custom-400',
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [400, 500],
                            alias: 'widgets::chart-widget.border',
                        ) => $color !== 'gray',
                    ])
                ></span>

                <span
                    x-ref="gridColorElement"
                    class="text-gray-200 dark:text-gray-800"
                ></span>

                <span
                    x-ref="textColorElement"
                    class="text-gray-500 dark:text-gray-400"
                ></span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>