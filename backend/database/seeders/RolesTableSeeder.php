<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('roles')->insert([
            ['libelle' => 'administrateur',  'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'superviseur', 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'formateur',    'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'apprenant',    'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'parent',     'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'caissier',    'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'auditeur',    'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'vendeur',    'created_at' => now(), 'updated_at' => now()],
        ]);

    }
}
