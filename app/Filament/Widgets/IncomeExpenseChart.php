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
        return $currentDate->month === $originalDate->month && $currentDate->year >= $originalDate->year;
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

    /**
     * Ajoute les contraintes de date selon la fréquence à une requête
     */
    private function addDateConstraints($query, Carbon $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->where(function ($subQ) use ($date) {
                // Pour les éléments non-annuels, utiliser start_date normalement
                $subQ->where('frequency', '!=', 'yearly')
                    ->where(function ($dateQ) use ($date) {
                        $dateQ->whereNull('start_date')
                            ->orWhere('start_date', '<=', $date->endOfMonth());
                    });
            })
            ->orWhere(function ($subQ) use ($date) {
                // Pour les éléments annuels, vérifier que la date d'occurrence est dans le passé ou présent
                $subQ->where('frequency', 'yearly')
                    ->where(function ($dateQ) use ($date) {
                        $dateQ->whereNull('start_date')
                            ->orWhere(function ($yearlyQ) use ($date) {
                                $yearlyQ->whereRaw('MONTH(start_date) = ?', [$date->month])
                                    ->whereRaw('YEAR(start_date) <= ?', [$date->year]);
                            });
                    });
            });
        });
    }

    /**
     * Génère les détails formatés pour le tooltip en comparant les données actuelles et précédentes
     */
    private function generateTooltipDetails(array $currentItems, array $previousItems, $allItems, Carbon $date, string $terminatedLabel): array
    {
        $details = [];

        foreach ($currentItems as $name => $amount) {
            // Récupérer l'item pour vérifier s'il a une date de fin
            $item = $allItems->firstWhere('name', $name);
            $hasEndDate = $item && $item->end_date;

            if (!isset($previousItems[$name])) {
                // Nouvel item
                $details[] = '🟢 ' . $name . ' : ' . number_format($amount, 2, ',', ' ') . ' € (nouveau' . ($terminatedLabel === 'terminée' ? 'elle' : '') . ')';
            } elseif ($previousItems[$name] != $amount) {
                // Item modifié
                $diff = $amount - $previousItems[$name];
                $sign = $diff > 0 ? '+' : '';
                $emoji = $diff > 0 ? '🟡' : '🟠';
                $details[] = $emoji . ' ' . $name . ' : ' . number_format($amount, 2, ',', ' ') . ' € (' . $sign . number_format($diff, 2, ',', ' ') . ' €)';
            } elseif ($hasEndDate && $item->end_date) {
                // Item temporaire inchangé (a une date de fin)
                $endDate = $item->end_date->format('d/m/Y');
                $remainingPayments = $this->calculateRemainingPayments($item->frequency, $date, $item->end_date);
                $details[] = '🟠 ' . $name . ' : ' . number_format($amount, 2, ',', ' ') . ' €';
                $details[] = '    📅 Fin le : ' . $endDate;
                $details[] = '    ⏳ Échéances restantes : ' . $remainingPayments;
            }
        }

        // Items qui se terminent ce mois
        foreach ($previousItems as $name => $amount) {
            if (!isset($currentItems[$name])) {
                $details[] = '🔴 ' . $name . ' : ' . $terminatedLabel;
            }
        }

        return $details;
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
            $allIncomes = $this->addDateConstraints(
                Income::where('is_active', true)->where('frequency', '!=', 'once'),
                $date
            )
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date->startOfMonth());
            })
            ->get();

            $monthlyIncomes = 0;
            $currentIncomes = [];

            // Calculer les revenus actuels
            foreach ($allIncomes as $income) {
                $amount = $this->getAmountForMonth($income->amount, $income->frequency, $date, $income->date);
                if ($amount > 0) {
                    $monthlyIncomes += $amount;
                    $currentIncomes[$income->name] = $amount;
                }
            }

            // Générer les détails des revenus pour le tooltip
            $incomeDetailForMonth = $this->generateTooltipDetails($currentIncomes, $previousIncomes, $allIncomes, $date, 'terminé');

            // Calculer toutes les dépenses pour ce mois (toutes fréquences)
            $allExpenses = $this->addDateConstraints(
                Expense::where('is_active', true)->where('frequency', '!=', 'once'),
                $date
            )
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date->startOfMonth());
            })
            ->get();

            $monthlyExpenses = 0;
            $currentExpenses = [];

            // Calculer les dépenses actuelles
            foreach ($allExpenses as $expense) {
                $amount = $this->getAmountForMonth($expense->amount, $expense->frequency, $date, $expense->date);
                if ($amount > 0) {
                    $monthlyExpenses += $amount;
                    $currentExpenses[$expense->name] = $amount;
                }
            }

            // Générer les détails des dépenses pour le tooltip
            $expenseDetailForMonth = $this->generateTooltipDetails($currentExpenses, $previousExpenses, $allExpenses, $date, 'terminée');

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
