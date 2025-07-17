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
        Schema::create('reponses_apprenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tentative_id')->constrained('tentatives')->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->text('reponse_texte')->nullable(); // Pour les réponses de type 'texte_libre'
            $table->json('reponse_ids')->nullable(); // Pour stocker les ID des options [1, 5]
            $table->boolean('est_correcte')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reponses_apprenant');
    }
};
