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
        Schema::table('inscriptions', function (Blueprint $table) {
                       $table->enum('statut_paiement', ['non_requis', 'en_attente', 'partiel', 'paye', 'rembourse'])
                  ->default('en_attente')
                  ->after('statut');

            // On AJOUTE une colonne pour suivre le montant total déjà versé.
            // La colonne sera placée après 'statut_paiement'.
            $table->decimal('montant_paye', 10, 2)
                  ->default(0.00);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropColumn(['statut_paiement', 'montant_paye']);
        });
    }
};
