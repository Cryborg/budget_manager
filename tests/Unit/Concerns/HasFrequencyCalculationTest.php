<?php

namespace Tests\Unit\Concerns;

use App\Filament\Concerns\HasFrequencyCalculation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\HtmlString;
use Tests\TestCase;

class HasFrequencyCalculationTest extends TestCase
{
    private $traitObject;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer une classe anonyme qui utilise le trait
        $this->traitObject = new class {
            use HasFrequencyCalculation;
        };
    }

    public function test_get_frequency_options_returns_correct_array(): void
    {
        $options = $this->traitObject::getFrequencyOptions();

        $expected = [
            'once' => 'Une fois',
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'yearly' => 'Annuel',
        ];

        $this->assertEquals($expected, $options);
    }

    public function test_get_frequency_form_components_returns_correct_components(): void
    {
        $components = $this->traitObject::getFrequencyFormComponents();

        $this->assertCount(3, $components);
        $this->assertInstanceOf(Select::class, $components[0]);
        $this->assertInstanceOf(DatePicker::class, $components[1]);
        $this->assertInstanceOf(DatePicker::class, $components[2]);

        // Vérifier le select des fréquences
        $this->assertEquals('frequency', $components[0]->getName());
        $this->assertEquals('once', $components[0]->getDefaultState());

        // Vérifier les DatePickers
        $this->assertEquals('start_date', $components[1]->getName());
        $this->assertEquals('end_date', $components[2]->getName());
    }

    public function test_calculate_total_amount_with_zero_amount(): void
    {
        $get = fn($key) => match($key) {
            'amount' => 0,
            'frequency' => 'monthly',
            default => null,
        };

        $result = $this->traitObject::calculateTotalAmount($get);
        
        $this->assertEquals('Montant total : 0,00 €', $result);
    }

    public function test_calculate_total_amount_with_once_frequency(): void
    {
        $get = fn($key) => match($key) {
            'amount' => 100.50,
            'frequency' => 'once',
            default => null,
        };

        $result = $this->traitObject::calculateTotalAmount($get);
        
        $this->assertEquals('Montant total : 100,50 €', $result);
    }

    public function test_calculate_total_amount_without_dates(): void
    {
        $get = fn($key) => match($key) {
            'amount' => 250.75,
            'frequency' => 'monthly',
            'start_date' => null,
            'end_date' => null,
            default => null,
        };

        $result = $this->traitObject::calculateTotalAmount($get);
        
        $this->assertEquals('Montant par occurrence : 250,75 €', $result);
    }

    public function test_calculate_total_amount_with_valid_monthly_period(): void
    {
        $get = fn($key) => match($key) {
            'amount' => 100,
            'frequency' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-01',
            default => null,
        };

        $result = $this->traitObject::calculateTotalAmount($get);
        
        $this->assertEquals('Total sur la période : 300,00 € (3 × 100,00 €)', $result);
    }

    public function test_calculate_total_amount_with_valid_daily_period(): void
    {
        $get = fn($key) => match($key) {
            'amount' => 10,
            'frequency' => 'daily',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-03',
            default => null,
        };

        $result = $this->traitObject::calculateTotalAmount($get);
        
        $this->assertEquals('Total sur la période : 30,00 € (3 × 10,00 €)', $result);
    }

    public function test_calculate_total_amount_with_valid_yearly_period(): void
    {
        $get = fn($key) => match($key) {
            'amount' => 1000,
            'frequency' => 'yearly',
            'start_date' => '2024-01-01',
            'end_date' => '2026-01-01',
            default => null,
        };

        $result = $this->traitObject::calculateTotalAmount($get);
        
        $this->assertEquals('Total sur la période : 3 000,00 € (3 × 1 000,00 €)', $result);
    }

    public function test_get_amount_calculation_placeholder_returns_html_string(): void
    {
        $get = fn($key) => match($key) {
            'amount' => 100,
            'frequency' => 'once',
            default => null,
        };

        $result = $this->traitObject::getAmountCalculationPlaceholder($get);
        
        $this->assertInstanceOf(HtmlString::class, $result);
        $this->assertStringContainsString('Montant total : 100,00 €', $result->toHtml());
        $this->assertStringContainsString('text-sm text-gray-600 mt-2', $result->toHtml());
    }

    public function test_format_frequency_for_table_returns_correct_labels(): void
    {
        $this->assertEquals('Une fois', $this->traitObject::formatFrequencyForTable('once'));
        $this->assertEquals('Quotidien', $this->traitObject::formatFrequencyForTable('daily'));
        $this->assertEquals('Hebdomadaire', $this->traitObject::formatFrequencyForTable('weekly'));
        $this->assertEquals('Mensuel', $this->traitObject::formatFrequencyForTable('monthly'));
        $this->assertEquals('Annuel', $this->traitObject::formatFrequencyForTable('yearly'));
        $this->assertEquals('unknown', $this->traitObject::formatFrequencyForTable('unknown'));
    }

    public function test_get_amount_form_component_returns_correct_configuration(): void
    {
        $component = $this->traitObject::getAmountFormComponent();

        $this->assertInstanceOf(TextInput::class, $component);
        $this->assertEquals('amount', $component->getName());
        $this->assertEquals('Montant', $component->getLabel());
        $this->assertTrue($component->isNumeric());
        $this->assertEquals(0.01, $component->getStep());
        $this->assertTrue($component->isRequired());
        $this->assertEquals(0.01, $component->getMinValue());
        // Note: isReactive() method doesn't exist in Filament, but reactive() is called in the trait
    }
}