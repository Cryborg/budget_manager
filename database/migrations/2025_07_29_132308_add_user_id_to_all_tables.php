<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Créer un utilisateur par défaut pour les données existantes si aucun n'existe
        if (!\App\Models\User::exists()) {
            \App\Models\User::create([
                'name' => 'Franck',
                'email' => 'franck@budget.local',
                'password' => bcrypt('password'),
            ]);
        }
        
        $defaultUserId = \App\Models\User::first()->id;

        // Ajouter user_id à la table banks
        Schema::table('banks', function (Blueprint $table) use ($defaultUserId) {
            $table->foreignId('user_id')->after('id')->default($defaultUserId)->constrained()->onDelete('cascade');
            $table->dropUnique(['code']); // Le code n'est unique que par utilisateur maintenant
            $table->unique(['code', 'user_id']);
        });

        // Ajouter user_id à la table bank_accounts  
        Schema::table('bank_accounts', function (Blueprint $table) use ($defaultUserId) {
            $table->foreignId('user_id')->after('id')->default($defaultUserId)->constrained()->onDelete('cascade');
            
            // Index pour améliorer les performances des requêtes par utilisateur
            $table->index('user_id');
        });

        // Mettre à jour les données existantes avec l'utilisateur par défaut
        \DB::table('banks')->whereNull('user_id')->update(['user_id' => $defaultUserId]);
        \DB::table('bank_accounts')->whereNull('user_id')->update(['user_id' => $defaultUserId]);

        // Supprimer les valeurs par défaut après avoir mis à jour les données existantes
        Schema::table('banks', function (Blueprint $table) {
            $table->foreignId('user_id')->change();
        });
        
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('user_id')->change();
        });

        // Les autres tables (incomes, expenses, transfers, balance_adjustments) 
        // n'ont pas besoin de user_id car elles sont liées aux bank_accounts
        // qui ont déjà le user_id
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->dropUnique(['code', 'user_id']);
            $table->unique(['code']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};