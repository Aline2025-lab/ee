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
        Schema::create('examens', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->enum('type', ['test', 'evaluation']);

            // Le statut de l'examen lui-même (le template)
            $table->enum('statut', ['brouillon', 'publié', 'archivé'])->default('brouillon');
            $table->string('description')->nullable();
            $table->integer('note_sur')->default(20);

          // LA PARTIE CRITIQUE : RELATION POLYMORPHE
            // Un examen peut appartenir à une Formation, un Module, ou une Leçon
            $table->morphs('examinable'); // Crée 'examinable_id' et 'examinable_type'

            $table->timestamps();

            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examens');
    }
};
