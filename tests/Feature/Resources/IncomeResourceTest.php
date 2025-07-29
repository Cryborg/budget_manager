<?php

namespace Tests\Feature\Resources;

use App\Filament\Resources\IncomeResource;
use App\Models\BankAccount;
use App\Models\Income;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_uses_correct_model(): void
    {
        $this->assertEquals(Income::class, IncomeResource::getModel());
    }

    public function test_resource_has_correct_labels(): void
    {
        $this->assertEquals('revenu', IncomeResource::getModelLabel());
        $this->assertEquals('revenus', IncomeResource::getPluralModelLabel());
    }

    public function test_resource_has_correct_navigation(): void
    {
        $this->assertEquals('heroicon-o-arrow-trending-up', IncomeResource::getNavigationIcon());
        $this->assertEquals('Revenus', IncomeResource::getNavigationLabel());
    }

    public function test_resource_form_components_are_defined(): void
    {
        // Test que les composants du trait sont bien disponibles  
        $components = IncomeResource::getFrequencyFormComponents();
        $this->assertCount(3, $components);
        
        $amountComponent = IncomeResource::getAmountFormComponent();
        $this->assertEquals('amount', $amountComponent->getName());
    }

    public function test_resource_has_correct_pages(): void
    {
        $pages = IncomeResource::getPages();
        
        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    public function test_resource_uses_traits(): void
    {
        $traits = class_uses(IncomeResource::class);
        
        // Depuis la refactorisation, IncomeResource utilise HasFinancialTransactionResource
        // qui inclut les deux traits HasCustomLabels et HasFrequencyCalculation
        $this->assertContains('App\Filament\Concerns\HasFinancialTransactionResource', $traits);
    }

    public function test_resource_frequency_options_are_correct(): void
    {
        $options = IncomeResource::getFrequencyOptions();
        
        $expected = [
            'once' => 'Une fois',
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'yearly' => 'Annuel',
        ];
        
        $this->assertEquals($expected, $options);
    }

    public function test_resource_frequency_table_formatting(): void
    {
        $this->assertEquals('Une fois', IncomeResource::formatFrequencyForTable('once'));
        $this->assertEquals('Mensuel', IncomeResource::formatFrequencyForTable('monthly'));
        $this->assertEquals('Annuel', IncomeResource::formatFrequencyForTable('yearly'));
    }

    public function test_resource_amount_component_validation(): void
    {
        $component = IncomeResource::getAmountFormComponent();
        
        $this->assertEquals('amount', $component->getName());
        $this->assertEquals('Montant', $component->getLabel());
        $this->assertTrue($component->isNumeric());
        $this->assertTrue($component->isRequired());
        $this->assertEquals(0.01, $component->getMinValue());
    }
}