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
        Schema::create('auditeurs', function (Blueprint $table) {
            $table->id();
            $table->string('matriculeFO')->unique();
            $table->string('specialite')->nullable();
            $table->string('CV')->nullable();
            $table->timestamp('date_derniere_action')->nullable();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditeurs');
    }
};
