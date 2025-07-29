<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Income;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class IncomeExpenseChart extends ChartWidget
{
    protected static ?string $heading = 'Évolution des revenus et dépenses';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.income-expense-chart';

    public ?string $filter = '24'; // 24 mois par défaut

    /**
     * Calcule le montant pour un mois donné selon la fréquence
     */
    private function getAmountForMonth(float $amount, string $frequency, Carbon $date, Carbon $originalDate): float
    {
        return match ($frequency) {
            'once' => 0, // Les montants ponctuels ne sont pas récurrents dans ce contexte
            'daily' => $amount * $date->daysInMonth, // Montant quotidien * nombre de jours dans le mois
            'weekly' => $amount * 4.33, // Approximation : 4.33 semaines par mois
            'monthly' => $amount, // Montant mensuel
            'yearly' => $this->isYearlyOccurence($date, $originalDate) ? $amount : 0, // Montant annuel seulement le bon mois
            default => 0,
        };
    }

    /**
     * Vérifie si une occurrence annuelle doit avoir lieu ce mois-ci
     */
    private function isYearlyOccurence(Carbon $currentDate, Carbon $originalDate): bool
    {
        return $currentDate->month === $originalDate->month;
    }

    /**
     * Calcule le nombre d'échéances restantes selon la fréquence
     */
    private function calculateRemainingPayments(string $frequency, Carbon $currentDate, Carbon $endDate): int
    {
        return match ($frequency) {
            'daily' => $currentDate->diffInDays($endDate) + 1,
            'weekly' => $currentDate->diffInWeeks($endDate) + 1,
            'monthly' => $currentDate->diffInMonths($endDate) + 1,
            'yearly' => $currentDate->diffInYears($endDate) + 1,
            default => 0,
        };
    }

    protected function getFilters(): ?array
    {
        return [
            '12' => '12 prochains mois',
            '24' => '24 prochains mois',
            '36' => '3 prochaines années',
            '60' => '5 prochaines années',
            '120' => '10 prochaines années',
            '300' => '25 prochaines années',
        ];
    }

    protected function getData(): array
    {
        $months = (int) $this->filter;

        $datasets = [];
        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $incomeDetails = [];
        $expenseDetails = [];
        $previousIncomes = [];
        $previousExpenses = [];

        // Générer les labels (mois futurs à partir de maintenant)
        for ($i = 0; $i < $months; $i++) {
            $date = Carbon::now()->startOfMonth()->addMonths($i);
            $labels[] = $date->format('M Y');

            // Calculer tous les revenus pour ce mois (toutes fréquences)
            $allIncomes = Income::where('is_active', true)
                ->where('frequency', '!=', 'once') // Exclure les revenus ponctuels
                ->where(function ($q) use ($date) {
                    $q->whereNull('start_date')
                        ->orWhere('start_date', '<=', $date->endOfMonth());
                })
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $date->startOfMonth());
                })
                ->get();

            $monthlyIncomes = 0;
            $currentIncomes = [];

            // Calculer les revenus actuels
            foreach ($allIncomes as $income) {
                $amount = $this->getAmountForMonth((float) $income->amount, $income->frequency, $date, $income->date);
                if ($amount > 0) {
                    $monthlyIncomes += $amount;
                    $currentIncomes[$income->name] = $amount;
                }
            }

            // Afficher seulement les changements et les revenus temporaires (avec date de fin)
            $incomeDetailForMonth = [];

            foreach ($currentIncomes as $name => $amount) {
                // Récupérer le revenu pour vérifier s'il a une date de fin
                $incomeItem = $allIncomes->firstWhere('name', $name);
                $hasEndDate = $incomeItem && $incomeItem->end_date;

                if (! isset($previousIncomes[$name])) {
                    // Nouveau revenu
                    $incomeDetailForMonth[] = '• '.$name;
                    $incomeDetailForMonth[] = '   → '.number_format($amount, 2, ',', ' ').' € (nouveau)';
                } elseif ($previousIncomes[$name] != $amount) {
                    // Revenu modifié
                    $diff = $amount - $previousIncomes[$name];
                    $sign = $diff > 0 ? '+' : '';
                    $incomeDetailForMonth[] = '• '.$name;
                    $incomeDetailForMonth[] = '   → '.number_format($amount, 2, ',', ' ').' € ('.$sign.number_format($diff, 2, ',', ' ').' €)';
                } elseif ($hasEndDate && $incomeItem->end_date) {
                    // Revenu temporaire inchangé (a une date de fin)
                    $endDate = $incomeItem->end_date->format('d/m/Y');
                    $remainingPayments = $this->calculateRemainingPayments($incomeItem->frequency, $date, $incomeItem->end_date);
                    $incomeDetailForMonth[] = '• '.$name;
                    $incomeDetailForMonth[] = '   → '.number_format($amount, 2, ',', ' ').' €';
                    $incomeDetailForMonth[] = '   ◦ Fin le : '.$endDate;
                    $incomeDetailForMonth[] = '   ◦ Échéances restantes : '.$remainingPayments;
                }
            }

            // Revenus qui se terminent ce mois
            foreach ($previousIncomes as $name => $amount) {
                if (! isset($currentIncomes[$name])) {
                    $incomeDetailForMonth[] = '• '.$name;
                    $incomeDetailForMonth[] = '   → terminé';
                }
            }

            // Calculer toutes les dépenses pour ce mois (toutes fréquences)
            $allExpenses = Expense::where('is_active', true)
                ->where('frequency', '!=', 'once') // Exclure les dépenses ponctuelles
                ->where(function ($q) use ($date) {
                    $q->whereNull('start_date')
                        ->orWhere('start_date', '<=', $date->endOfMonth());
                })
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $date->startOfMonth());
                })
                ->get();

            $monthlyExpenses = 0;
            $currentExpenses = [];

            // Calculer les dépenses actuelles
            foreach ($allExpenses as $expense) {
                $amount = $this->getAmountForMonth((float) $expense->amount, $expense->frequency, $date, $expense->date);
                if ($amount > 0) {
                    $monthlyExpenses += $amount;
                    $currentExpenses[$expense->name] = $amount;
                }
            }

            // Afficher seulement les changements et les dépenses temporaires (avec date de fin)
            $expenseDetailForMonth = [];

            foreach ($currentExpenses as $name => $amount) {
                // Récupérer la dépense pour vérifier si elle a une date de fin
                $expenseItem = $allExpenses->firstWhere('name', $name);
                $hasEndDate = $expenseItem && $expenseItem->end_date;

                if (! isset($previousExpenses[$name])) {
                    // Nouvelle dépense
                    $expenseDetailForMonth[] = '• '.$name;
                    $expenseDetailForMonth[] = '   → '.number_format($amount, 2, ',', ' ').' € (nouvelle)';
                } elseif ($previousExpenses[$name] != $amount) {
                    // Dépense modifiée
                    $diff = $amount - $previousExpenses[$name];
                    $sign = $diff > 0 ? '+' : '';
                    $expenseDetailForMonth[] = '• '.$name;
                    $expenseDetailForMonth[] = '   → '.number_format($amount, 2, ',', ' ').' € ('.$sign.number_format($diff, 2, ',', ' ').' €)';
                } elseif ($hasEndDate && $expenseItem->end_date) {
                    // Dépense temporaire inchangée (a une date de fin)
                    $endDate = $expenseItem->end_date->format('d/m/Y');
                    $remainingPayments = $this->calculateRemainingPayments($expenseItem->frequency, $date, $expenseItem->end_date);
                    $expenseDetailForMonth[] = '• '.$name;
                    $expenseDetailForMonth[] = '   → '.number_format($amount, 2, ',', ' ').' €';
                    $expenseDetailForMonth[] = '   ◦ Fin le : '.$endDate;
                    $expenseDetailForMonth[] = '   ◦ Échéances restantes : '.$remainingPayments;
                }
            }

            // Dépenses qui se terminent ce mois
            foreach ($previousExpenses as $name => $amount) {
                if (! isset($currentExpenses[$name])) {
                    $expenseDetailForMonth[] = '• '.$name;
                    $expenseDetailForMonth[] = '   → terminée';
                }
            }

            $incomeData[] = round($monthlyIncomes, 2);
            $expenseData[] = round($monthlyExpenses, 2);

            // Ne stocker que les détails non-vides pour économiser l'espace
            if (! empty($incomeDetailForMonth)) {
                $incomeDetails[$i] = $incomeDetailForMonth;
            }
            if (! empty($expenseDetailForMonth)) {
                $expenseDetails[$i] = $expenseDetailForMonth;
            }

            // Sauvegarder l'état actuel pour la comparaison du mois suivant
            $previousIncomes = $currentIncomes;
            $previousExpenses = $currentExpenses;
        }

        // Dataset des revenus
        $datasets[] = [
            'label' => 'Revenus',
            'data' => $incomeData,
            'borderColor' => '#22c55e',
            'backgroundColor' => '#22c55e20',
            'tension' => 0.3,
            'fill' => false,
            'borderWidth' => 2,
            'pointRadius' => 4,
            'pointHoverRadius' => 6,
            'pointBackgroundColor' => '#22c55e',
            'pointBorderWidth' => 0,
        ];

        // Dataset des dépenses
        $datasets[] = [
            'label' => 'Dépenses',
            'data' => $expenseData,
            'borderColor' => '#ef4444',
            'backgroundColor' => '#ef444420',
            'tension' => 0.3,
            'fill' => false,
            'borderWidth' => 2,
            'pointRadius' => 4,
            'pointHoverRadius' => 6,
            'pointBackgroundColor' => '#ef4444',
            'pointBorderWidth' => 0,
        ];

        return [
            'datasets' => $datasets,
            'labels' => $labels,
            'incomeDetails' => $incomeDetails,
            'expenseDetails' => $expenseDetails,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'layout' => [
                'padding' => [
                    'top' => 20,
                    'bottom' => 10,
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(255, 255, 255, 0.5)',
                        'lineWidth' => 1,
                        'drawBorder' => false,
                        'drawOnChartArea' => true,
                        'drawTicks' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Montant (€)',
                        'font' => [
                            'size' => 14,
                            'weight' => 'bold',
                        ],
                    ],
                    'ticks' => [
                        'stepSize' => 500,
                        'maxTicksLimit' => 8,
                        'callback' => 'function(value) { return new Intl.NumberFormat("fr-FR", { style: "currency", currency: "EUR" }).format(value); }',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(255, 255, 255, 0.05)',
                        'lineWidth' => 1,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Période',
                        'font' => [
                            'size' => 14,
                            'weight' => 'bold',
                        ],
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(0, 0, 0, 0.9)',
                    'titleColor' => '#ffffff',
                    'bodyColor' => '#ffffff',
                    'borderColor' => 'rgba(255, 255, 255, 0.3)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'displayColors' => true,
                    'titleFont' => [
                        'size' => 14,
                        'weight' => 'bold',
                    ],
                    'bodyFont' => [
                        'size' => 13,
                    ],
                    'padding' => 12,
                ],
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'align' => 'center',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'font' => [
                            'size' => 12,
                        ],
                        'padding' => 15,
                        'boxWidth' => 8,
                        'boxHeight' => 8,
                    ],
                ],
            ],
        ];
    }
}
