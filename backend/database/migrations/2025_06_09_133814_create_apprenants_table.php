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
        Schema::create('apprenants', function (Blueprint $table) {
            $table->id();
            $table->string('matriculeAP')->unique();
            $table->string('niveau_etude')->nullable();
            $table->string('statut_actuel')->default('Actif');
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('utilisateurs') ->onDelete('set null'); 
            $table->timestamp('derniere_connexion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apprenants');
    }
};
