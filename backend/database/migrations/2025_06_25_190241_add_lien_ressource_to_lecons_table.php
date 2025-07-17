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
        Schema::table('lecons', function (Blueprint $table) {

            // On ajoute une colonne pour stocker l'URL.
            // Elle est nullable car toutes les leçons n'auront pas forcément un lien externe.
            $table->string('lien_ressource', 2048)->nullable()->after('contenu');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecons', function (Blueprint $table) {
            $table->dropColumn('lien_ressource');
        });
    }
};
