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
- Optimisé pour éviter N+1 : charge toutes les données d'un coup au début avec groupBy()

### Options du graphique
- Padding : défini dans `getOptions()` sous `layout.padding`
- Légende : interligne défini dans `plugins.legend.labels.padding`

## Widget IncomeExpenseChart

### Tooltips intelligents
- Affichent uniquement les changements et les revenus/dépenses temporaires (avec date de fin)
- Format avec sous-puces pour les infos détaillées :
  - `• Nom : montant`
  - `  ◦ Fin le : date`
  - `  ◦ Échéances restantes : nombre`
- Vue personnalisée dans `/resources/views/filament/widgets/income-expense-chart.blade.php`
- Gestion des callbacks de tooltip reconfigurés lors des changements de filtre

### Hauteur du graphique
- Utilise la même condition que AccountEvolutionChart dans le template vendor
- 480px de hauteur

## Fréquences disponibles
- Définies dans les migrations : 'once', 'daily', 'weekly', 'monthly', 'yearly'
- Les transactions ponctuelles ('once') sont exclues des calculs de prévision

## Tests
- Utiliser Laravel Sail pour exécuter les tests : `./vendor/bin/sail test`
- Factories créées pour tous les modèles avec des states utiles (active/inactive, monthly/yearly, etc.)
- Tests organisés par type : Unit (models, traits) et Feature (widgets, resources)
- Toujours utiliser RefreshDatabase trait pour les tests avec base de données

### Pièges à éviter dans les tests
- `decimal:2` cast retourne une string, pas un float - utiliser `assertIsString()` 
- Les propriétés statiques Filament peuvent être protected - éviter de les tester directement
- Les méthodes comme `getData()` des widgets sont souvent protected - tester plutôt l'instantiation
- Pour Filament Forms/Tables, éviter `Form::make()` sans paramètre - tester plutôt les méthodes des traits
- Les relations BankAccount : `transfersFrom`/`transfersTo` pas `fromTransfers`/`toTransfers`
- Ajouter `HasFactory` trait à tous les modèles pour les factories

## Qualité de code
- Laravel Pint pour le style : `./vendor/bin/sail vendor/bin/pint`  
- PHPStan niveau 5 pour l'analyse statique : `./vendor/bin/sail exec laravel.test ./vendor/bin/phpstan analyse`
- Annotations PHPDoc nécessaires sur les modèles pour PHPStan
- Traits créés pour factoriser : HasFrequencyCalculation, HasCustomLabels

## Modèles et casts
- Tous les modèles ont des casts appropriés (date, decimal:2, boolean)
- Utiliser directement les propriétés castées plutôt que Carbon::parse()
- Les annotations @property sont importantes pour PHPStan

## Notes importantes
- Ne pas utiliser `/resources/views/filament/widgets/large-chart.blade.php` - ce template n'est pas utilisé
- Les modifications de style doivent se faire dans le template Filament vendor ou dans les options du widget
- Composer doit être lancé via Sail : `./vendor/bin/sail composer`