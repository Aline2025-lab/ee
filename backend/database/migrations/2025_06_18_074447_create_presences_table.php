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
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('seance_id')->constrained()->onDelete('cascade');
            $table->boolean('est_present')->default(false);
            $table->string('justificatif')->nullable();
            $table->text('remarque')->nullable();
            $table->timestamps();

            $table->unique(['apprenant_id', 'seance_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
