<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UtilisateursTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
       DB::table('utilisateurs')->insert([
            
            [
                'role_id' => 8,
                'nom' => 'NOAH EDOUARD',
                'prenom' => 'Michel',
                'genre' => 'Masculin',
                'date_naissance' => '1995-11-15',
                'email' => 'noa.edouardmichel@gmail.com@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
            [
                'role_id' => 7,
                'nom' => 'MAGNE',
                'prenom' => 'Chris',
                'genre' => 'Féminin',
                'date_naissance' => '2000-11-15',
                'email' => 'christmagne0310@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
            [
                'role_id' => 6,
                'nom' => 'EKLOU',
                'prenom' => 'Kokou',
                'genre' => 'Masculin',
                'date_naissance' => '1996-08-05',
                'email' => 'kokou.eklou@institutsaintjean.org',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
            [
                'role_id' => 5,
                'nom' => 'EKLOU',
                'prenom' => 'mbang',
                'genre' => 'Masculin',
                'date_naissance' => '1990-02-25',
                'email' => 'mbangeklou@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
            [
                'role_id' => 4,
                'nom' => 'Songmene',
                'prenom' => 'Marie',
                'genre' => 'Féminin',
                'date_naissance' => '1995-09-10',
                'email' => 'songmenebelviane@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
            [
                'role_id' => 3,
                'nom' => 'Lado',
                'prenom' => 'Belviane',
                'genre' => 'Feminin',
                'date_naissance' => '1980-03-20',
                'email' => 'belviane.songmene@institutsaintjean.org',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
            [
                'role_id' => 2,
                'nom' => 'OWONO',
                'prenom' => 'Flore',
                'genre' => 'Feminin',
                'date_naissance' => '1985-06-15',
                'email' => 'aline.onana@institutsaintjean.org',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
            [
                'role_id' => 1,
                'nom' => 'ONANA',
                'prenom' => 'Aline',
                'genre' => 'Feminin',
                'date_naissance' => '2000-01-01',
                'email' => 'alineonana070@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        ]);
    }
}
