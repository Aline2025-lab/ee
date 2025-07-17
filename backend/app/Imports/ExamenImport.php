<?php

namespace App\Imports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ExamenImport implements ToCollection, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Question([
            //
        ]);
    }

    public function collection(Collection $rows)
    {
        // Cette méthode recevra une collection où chaque élément est une ligne du fichier
        // Le code pour traiter ces lignes ira ici.
        // Pour l'instant, on peut juste afficher les données pour vérifier.
        // dd($rows);
    }
}
