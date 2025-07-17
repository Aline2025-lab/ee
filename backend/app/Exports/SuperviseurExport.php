<?php

namespace App\Exports;

use App\Models\Superviseur;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SuperviseurExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Superviseur::with('utilisateurs')
            ->get()
            ->map(function ($sup) {
                return [
                    'Nom' => $sup->utilisateurs->nom,
                    'Prenom' => $sup->utilisateurs->prenom,
                    'Matricule' => $sup->matricule,
                    'Email' => $sup->utilisateurs->email,
                    'Date de naissance' => $sup->utilisateurs->date_naissance,
                    'Actif' => $sup->utilisateurs->is_active ? 'Oui' : 'Non',
                ];
            });
    }

    public function headings(): array
    {
        return ['Nom', 'Prenom', 'Matricule', 'Email', 'Date de naissance', 'Actif'];
    }
}
