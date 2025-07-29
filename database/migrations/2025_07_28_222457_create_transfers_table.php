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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('bank_accounts')->onDelete('cascade');
            $table->foreignId('to_account_id')->constrained('bank_accounts')->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->enum('frequency', ['once', 'daily', 'weekly', 'monthly', 'yearly'])->default('once');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
