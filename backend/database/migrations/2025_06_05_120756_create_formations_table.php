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
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->string('nom_formation');
            $table->text('libelle_formation');
            $table->date('date_debutf');
            $table->date('date_finf');
            $table->integer('nombre_seancef');
            $table->integer('volume_horaire');
            $table->boolean('certifiante')->default(false);
            $table->decimal('prix_certification', 10, 2)->nullable();
            $table->decimal('prix', 10, 2)->default(0);
            $table->string('statut')->default('en cours');
            $table->text('objectif')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};
