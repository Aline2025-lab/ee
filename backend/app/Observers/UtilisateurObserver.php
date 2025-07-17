<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Formateur;
use App\Models\Superviseur;
use App\Models\Administrateur;
use App\Models\Caissier;
use App\Models\Auditeur;
use App\Models\Apprenant;
use App\Models\Vendeur;
use App\Models\Parents;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UtilisateurObserver
{
    /**
     * Handle the Utilisateur "created" event.
    */
    public function created(User $utilisateur): void
    {
        $utilisateur->loadMissing('role');

        $roleAbbr = strtoupper(substr($utilisateur->role->libelle, 0, 2)); // 'AD', 'FO', etc.
        $dateAjout = now(); // ou $utilisateur->created_at si disponible
        $jourAjout = $dateAjout->format('d'); // '24'
        
        $nom = strtoupper(substr($utilisateur->nom, 0, 2)); // 'DI'
        $prenom = $utilisateur->prenom;
        $jourNaissance = Carbon::parse($utilisateur->date_naissance)->format('d'); // '15'
        $finPrenom = strtoupper(substr($prenom, -2)); // 'EL'
        $anneeNaissance = Carbon::parse($utilisateur->date_naissance)->format('y'); // '95'

        $matricule = $roleAbbr . $jourAjout . $nom . $jourNaissance . $finPrenom . $anneeNaissance;


        switch ($utilisateur->role_id) {
            case 1: // administrateur
                Administrateur::create([
                    'utilisateur_id' => $utilisateur->id,
                    'matriculeAD' => $matricule
                    // ajouter les champs spécifiques si nécessaires
                ]);
                break;
            case 2: // superviseur
                Superviseur::create([
                    'utilisateur_id' => $utilisateur->id,
                    'matriculeSU' => $matricule
                ]);
                break;
            case 3: // formateur
                Formateur::create([
                    'utilisateur_id' => $utilisateur->id,
                    'matriculeAD' => $matricule
                ]);
                break;
            case 4: // apprenant
                Apprenant::create([
                    'utilisateur_id' => $utilisateur->id,
                    'matriculeAP' => $matricule
                ]);
                break;
            case 5: // parents
                Parents::create([
                    'utilisateur_id' => $utilisateur->id,
                    'matriculePA' => $matricule
                ]);
                break; 
            case 6: // caissier
                Caissier::create([
                    'utilisateur_id' => $utilisateur->id,
                    'matriculeCA' => $matricule
                
                ]);
                break;
            case 7: // auditeur
                Auditeur::create([
                    'utilisateur_id' => $utilisateur->id,
                    'matriculeFO' => $matricule
                ]);
                break;

            case 8: // vendeur
                Vendeur::create([
                    'utilisateur_id' => $utilisateur->id,
                    'matriculeVE' => $matricule
                ]);
                break;
             
        }
    }
    

    /**
     * Handle the Utilisateur "updated" event.
     */
    public function updated(User $utilisateur): void
    {
        //
    }

    /**
     * Handle the Utilisateur "deleted" event.
     */
    public function deleted(User $utilisateur): void
    {
        //
    }

    /**
     * Handle the Utilisateur "restored" event.
     */
    public function restored(User $utilisateur): void
    {
        //
    }

    /**
     * Handle the Utilisateur "force deleted" event.
     */
    public function forceDeleted(User $utilisateur): void
    {
        //
    }
}
