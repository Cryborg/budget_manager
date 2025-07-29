<?php

namespace Tests\Feature\Widgets;

use App\Filament\Widgets\AccountEvolutionChart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountEvolutionChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_can_be_instantiated(): void
    {
        $widget = new AccountEvolutionChart();
        
        $this->assertNotNull($widget);
        $this->assertInstanceOf(AccountEvolutionChart::class, $widget);
    }

    public function test_widget_has_default_filter(): void
    {
        $widget = new AccountEvolutionChart();
        
        $this->assertEquals('24', $widget->filter);
    }

    public function test_widget_is_chart_widget(): void
    {
        $widget = new AccountEvolutionChart();
        
        $this->assertInstanceOf('Filament\Widgets\ChartWidget', $widget);
    }
}