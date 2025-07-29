<?php

namespace App\Filament\Concerns;

use Filament\Forms;
use Illuminate\Support\HtmlString;

trait HasFrequencyCalculation
{
    public static function getFrequencyOptions(): array
    {
        return [
            'once' => 'Une fois',
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'yearly' => 'Annuel',
        ];
    }

    public static function getFrequencyFormComponents(): array
    {
        return [
            Forms\Components\Select::make('frequency')
                ->label('Fréquence')
                ->options(static::getFrequencyOptions())
                ->default('once')
                ->reactive(),

            Forms\Components\DatePicker::make('start_date')
                ->label('Date de début')
                ->visible(fn (callable $get) => in_array($get('frequency'), ['daily', 'weekly', 'monthly', 'yearly']))
                ->reactive(),

            Forms\Components\DatePicker::make('end_date')
                ->label('Date de fin')
                ->visible(fn (callable $get) => in_array($get('frequency'), ['daily', 'weekly', 'monthly', 'yearly']))
                ->after('start_date')
                ->reactive(),
        ];
    }

    public static function calculateTotalAmount(callable $get): string
    {
        $amount = (float) ($get('amount') ?? 0);
        $frequency = $get('frequency') ?? 'once';
        $startDate = $get('start_date');
        $endDate = $get('end_date');

        if ($amount <= 0) {
            return 'Montant total : 0,00 €';
        }

        if ($frequency === 'once') {
            return 'Montant total : '.number_format($amount, 2, ',', ' ').' €';
        }

        if (! $startDate || ! $endDate) {
            return 'Montant par occurrence : '.number_format($amount, 2, ',', ' ').' €';
        }

        try {
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            // Calculer le nombre réel d'échéances selon la fréquence
            $occurrences = static::calculateOccurrences($start, $end, $frequency);

            $total = $amount * $occurrences;

            return 'Total sur la période : '.number_format($total, 2, ',', ' ').' € ('.$occurrences.' × '.number_format($amount, 2, ',', ' ').' €)';
        } catch (\Exception $e) {
            return 'Montant par occurrence : '.number_format($amount, 2, ',', ' ').' €';
        }
    }

    /**
     * Calcule le nombre réel d'échéances entre deux dates selon la fréquence
     */
    protected static function calculateOccurrences(\Carbon\Carbon $start, \Carbon\Carbon $end, string $frequency): int
    {
        if ($start->gt($end)) {
            return 0;
        }

        $occurrences = 0;
        $current = $start->copy();

        // Compter les échéances réelles en itérant selon la fréquence
        while ($current->lte($end)) {
            $occurrences++;

            switch ($frequency) {
                case 'daily':
                    $current->addDay();
                    break;
                case 'weekly':
                    $current->addWeek();
                    break;
                case 'monthly':
                    $current->addMonth();
                    break;
                case 'yearly':
                    $current->addYear();
                    break;
                default:
                    break 2; // Sortir de la boucle while
            }
        }

        return $occurrences;
    }

    public static function getAmountCalculationPlaceholder(callable $get): HtmlString
    {
        return new HtmlString('<div class="text-sm text-gray-600 mt-2">'.static::calculateTotalAmount($get).'</div>');
    }

    public static function formatFrequencyForTable($frequency): string
    {
        return match ($frequency) {
            'once' => 'Une fois',
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'yearly' => 'Annuel',
            default => $frequency,
        };
    }

    public static function getAmountFormComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('amount')
            ->label('Montant')
            ->numeric()
            ->step(0.01)
            ->required()
            ->minValue(0.01)
            ->reactive();
    }
}
