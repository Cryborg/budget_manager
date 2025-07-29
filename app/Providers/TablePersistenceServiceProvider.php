<?php

namespace App\Providers;

use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class TablePersistenceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Étendre la classe Table pour ajouter automatiquement les attributs de persistance
        Table::macro('withPersistence', function () {
            $resourceName = class_basename($this->getLivewire()::class);
            
            return $this->extraAttributes([
                'data-table-name' => strtolower($resourceName),
                'data-persistence-enabled' => 'true'
            ]);
        });
    }
}