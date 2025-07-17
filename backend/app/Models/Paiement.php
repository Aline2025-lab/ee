<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Paiement extends Model
{
    use HasFactory;
    protected $table = 'paiements';

    // Définir les champs qui peuvent être remplis
    protected $fillable = [
        'apprenant_id',
        'formation_id',
        'montant',
        'methode',
        'type_paiement',
        'reference',
        'motif',
        'statut',
        'date_paiement',
        // On va ajouter ces deux champs pour la traçabilité
        'caissier_id',
        'auditeur_id',
        'date_confirmation',
    ];

    // Relations
    public function apprenant() { return $this->belongsTo(Apprenant::class); }
    public function formation() { return $this->belongsTo(Formation::class); }
    public function caissier() { return $this->belongsTo(User::class, 'caissier_id'); }
    public function auditeur() { return $this->belongsTo(User::class, 'auditeur_id'); }
}
