<?php

namespace App\Filament\Widgets;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AccountEvolutionChart extends ChartWidget
{
    protected static ?string $heading = 'Prévisions d\'évolution des comptes';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '24'; // 24 mois par défaut

    /**
     * Calcule le montant pour un mois donné selon la fréquence
     */
    private function getAmountForMonth(float $amount, string $frequency, Carbon $date, Carbon $originalDate): float
    {
        return match ($frequency) {
            'once' => 0, // Les montants ponctuels ne sont pas récurrents dans ce contexte
            'daily' => $amount * $date->daysInMonth, // Montant quotidien * nombre de jours dans le mois
            'weekly' => $amount * 4.33, // Approximation : 4.33 semaines par mois
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
     * Vérifie si un élément (income/expense/transfer) est actif pour une date donnée
     */
    private function isItemActiveForDate($item, Carbon $date): bool
    {
        if ($item->frequency === 'yearly') {
            // Pour les éléments annuels, vérifier que la date d'occurrence est dans le passé ou présent
            $startDateCheck = $item->start_date === null || 
                ($item->start_date->month === $date->month && $item->start_date->year <= $date->year);
        } else {
            // Pour les autres fréquences, logique normale
            $startDateCheck = $item->start_date === null || $item->start_date <= $date->endOfMonth();
        }
        
        $endDateCheck = $item->end_date === null || $item->end_date >= $date->startOfMonth();
        
        return $startDateCheck && $endDateCheck;
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
        $accounts = BankAccount::with('bank')->where('is_active', true)->get();

        // Charger toutes les données d'un coup pour éviter N+1
        $allIncomes = Income::with('bankAccount')
            ->where('is_active', true)
            ->where('frequency', '!=', 'once')
            ->get()
            ->groupBy('bank_account_id');

        $allExpenses = Expense::with('bankAccount')
            ->where('is_active', true)
            ->where('frequency', '!=', 'once')
            ->get()
            ->groupBy('bank_account_id');

        $allTransfersIn = Transfer::with(['fromAccount', 'toAccount'])
            ->where('is_active', true)
            ->where('frequency', '!=', 'once')
            ->get()
            ->groupBy('to_account_id');

        $allTransfersOut = Transfer::with(['fromAccount', 'toAccount'])
            ->where('is_active', true)
            ->where('frequency', '!=', 'once')
            ->get()
            ->groupBy('from_account_id');

        $datasets = [];
        $labels = [];

        // Générer les labels (mois futurs à partir de maintenant)
        for ($i = 0; $i < $months; $i++) {
            $date = Carbon::now()->startOfMonth()->addMonths($i);
            $labels[] = $date->format('M Y');
        }

        // Couleurs distinctes pour chaque compte (pas par banque)
        $colors = [
            '#e11d48', // Rouge vif
            '#0ea5e9', // Bleu ciel
            '#22c55e', // Vert
            '#f59e0b', // Orange
            '#8b5cf6', // Violet
            '#06b6d4', // Cyan
            '#84cc16', // Lime
            '#f97316', // Orange foncé
            '#ec4899', // Rose
            '#64748b', // Gris bleu
            '#dc2626', // Rouge foncé
            '#2563eb', // Bleu foncé
        ];

        foreach ($accounts as $index => $account) {
            $data = [];
            $currentBalance = $account->current_balance; // Partir du solde actuel

            // Récupérer tous les ajustements pour ce compte, triés par date
            /** @var \Illuminate\Support\Collection<string, \App\Models\BalanceAdjustment> $adjustments */
            $adjustments = $account->balanceAdjustments()
                ->where('is_active', true)
                ->orderBy('adjustment_date')
                ->get()
                ->keyBy(function ($item) {
                    /** @var \App\Models\BalanceAdjustment $item */
                    return $item->adjustment_date->format('Y-m');
                });

            for ($i = 0; $i < $months; $i++) {
                $date = Carbon::now()->startOfMonth()->addMonths($i);
                $dateKey = $date->format('Y-m');

                // Vérifier s'il y a un ajustement pour ce mois
                if ($adjustments->has($dateKey)) {
                    $adjustment = $adjustments->get($dateKey);
                    $currentBalance = $adjustment->actual_balance;
                    $data[] = round($currentBalance, 2);

                    continue;
                }

                // Pour le premier mois, partir du solde actuel
                if ($i === 0) {
                    $monthlyBalance = $currentBalance;
                } else {
                    // Calculer tous les revenus récurrents pour ce compte (toutes fréquences)
                    $accountIncomes = $allIncomes->get($account->id, collect());

                    $monthlyIncomes = 0;
                    foreach ($accountIncomes as $income) {
                        if ($this->isItemActiveForDate($income, $date)) {
                            $monthlyIncomes += $this->getAmountForMonth($income->amount, $income->frequency, $date, $income->date);
                        }
                    }

                    // Calculer toutes les dépenses récurrentes pour ce compte (toutes fréquences)
                    $accountExpenses = $allExpenses->get($account->id, collect());

                    $monthlyExpenses = 0;
                    foreach ($accountExpenses as $expense) {
                        if ($this->isItemActiveForDate($expense, $date)) {
                            $monthlyExpenses += $this->getAmountForMonth($expense->amount, $expense->frequency, $date, $expense->date);
                        }
                    }

                    // Calculer tous les virements entrants récurrents (toutes fréquences)
                    $accountTransfersIn = $allTransfersIn->get($account->id, collect());

                    $transfersIn = 0;
                    foreach ($accountTransfersIn as $transfer) {
                        if ($this->isItemActiveForDate($transfer, $date)) {
                            $transfersIn += $this->getAmountForMonth($transfer->amount, $transfer->frequency, $date, $transfer->date);
                        }
                    }

                    // Calculer tous les virements sortants récurrents (toutes fréquences)
                    $accountTransfersOut = $allTransfersOut->get($account->id, collect());

                    $transfersOut = 0;
                    foreach ($accountTransfersOut as $transfer) {
                        if ($this->isItemActiveForDate($transfer, $date)) {
                            $transfersOut += $this->getAmountForMonth($transfer->amount, $transfer->frequency, $date, $transfer->date);
                        }
                    }

                    // Appliquer les changements au solde
                    $currentBalance += $monthlyIncomes - $monthlyExpenses + $transfersIn - $transfersOut;
                }

                $data[] = round($currentBalance, 2);
            }

            $color = $colors[$index % count($colors)];

            $datasets[] = [
                'label' => $account->name,
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => $color.'20',
                'tension' => 0.3,
                'fill' => false,
                'borderWidth' => 2,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
                'pointBackgroundColor' => $color,
                'pointBorderWidth' => 0,
            ];
        }

        // Ajouter une ligne pour le total
        $totalData = [];
        for ($i = 0; $i < count($labels); $i++) {
            $monthTotal = 0;
            foreach ($datasets as $dataset) {
                $monthTotal += $dataset['data'][$i] ?? 0;
            }
            $totalData[] = round($monthTotal, 2);
        }

        $datasets[] = [
            'label' => 'TOTAL',
            'data' => $totalData,
            'borderColor' => '#e5e7eb',
            'backgroundColor' => '#e5e7eb20',
            'tension' => 0.3,
            'borderWidth' => 3,
            'fill' => false,
            'pointRadius' => 5,
            'pointHoverRadius' => 7,
            'pointBackgroundColor' => '#e5e7eb',
            'pointBorderWidth' => 0,
        ];

        return [
            'datasets' => $datasets,
            'labels' => $labels,
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
                        'text' => 'Solde (€)',
                        'font' => [
                            'size' => 14,
                            'weight' => 'bold',
                        ],
                    ],
                    'ticks' => [
                        'stepSize' => 1000,
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
                    'position' => 'left',
                    'align' => 'start',
                    'maxWidth' => 200,
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
