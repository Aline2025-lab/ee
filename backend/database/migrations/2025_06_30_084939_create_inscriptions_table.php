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
       Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formation_id')->constrained()->onDelete('cascade');
            $table->foreignId('apprenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('formateur_id')->constrained('formateurs')->onDelete('cascade');
            $table->date('date_inscription')->default(now());
            $table->enum('statut', ['en_attente', 'accepte', 'refuse'])->default('en_attente');

            $table->
    $table->foreignId('apprenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('inscrit_par')->constrained('users')->onDelete('cascade'); // superviseur/admin
    $table->date('date_inscription')->default(now());
    $table->enum('statut', ['en_attente', 'accepte', 'refuse'])->default('en_attente');
    $table->boolean('paiement_effectue')->default(false);
            $table->timestamps();


            $table->unique(['formation_id', 'apprenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
