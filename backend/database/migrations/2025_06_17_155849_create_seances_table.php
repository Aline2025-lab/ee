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
        Schema::create('seances', function (Blueprint $table) {
            $table->id();

                $table->string('titre');
                $table->text('description')->nullable();
                $table->date('date');
                $table->time('heure_debut');
                $table->time('heure_fin');
                $table->string('type_seance');
                $table->string('statut');      

                $table->unsignedBigInteger('classe_id'); 
                $table->unsignedBigInteger('formation_id');
                $table->unsignedBigInteger('formateur_id');
                $table->timestamps();

                $table->foreign('classe_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('formation_id')->references('id')->on('formations')->onDelete('cascade');
                $table->foreign('formateur_id')->references('id')->on('formateurs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};
