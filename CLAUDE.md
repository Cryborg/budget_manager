# Claude - Notes techniques pour ce projet

## Architecture du projet
- Laravel avec Filament Admin Panel
- Base de données SQLite
- Modèles principaux : BankAccount, Income, Expense, Transfer, BalanceAdjustment

## Widget AccountEvolutionChart

### Hauteur du graphique
- La hauteur est définie dans `/resources/views/vendor/filament-widgets/chart-widget.blade.php`
- Chercher la condition `@if($this instanceof \App\Filament\Widgets\AccountEvolutionChart)`
- Actuellement définie à 480px (augmentée de 20% depuis 400px)
- Les propriétés comme `$maxHeight` dans la classe PHP ne fonctionnent pas pour ce widget

### Calcul des prévisions
- Le widget prend en compte toutes les fréquences : 'once', 'daily', 'weekly', 'monthly', 'yearly'
- Les revenus/dépenses annuels ne sont comptés QUE le mois où ils tombent (pas étalés sur l'année)
- La méthode `getAmountForMonth()` gère la conversion selon la fréquence
- Pour les yearly : vérifie si le mois courant correspond au mois de la date originale

### Options du graphique
- Padding : défini dans `getOptions()` sous `layout.padding`
- Légende : interligne défini dans `plugins.legend.labels.padding`

## Fréquences disponibles
- Définies dans les migrations : 'once', 'daily', 'weekly', 'monthly', 'yearly'
- Les transactions ponctuelles ('once') sont exclues des calculs de prévision

## Notes importantes
- Ne pas utiliser `/resources/views/filament/widgets/large-chart.blade.php` - ce template n'est pas utilisé
- Les modifications de style doivent se faire dans le template Filament vendor ou dans les options du widget