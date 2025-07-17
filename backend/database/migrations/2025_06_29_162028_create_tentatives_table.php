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
        Schema::create('tentatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained()->onDelete('cascade');
            $table->foreignId('apprenant_id')->comment('ID de l\'apprenant')->constrained('apprenants')->onDelete('cascade');
            $table->enum('statut', ['en_cours', 'terminee', 'corrigee', 'publie'])->default('en_cours'); 
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('heure_debut')->useCurrent();
            $table->timestamp('heure_fin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tentatives');
    }
};
