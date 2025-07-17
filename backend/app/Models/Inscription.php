<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
//use Illuminate\Database\Eloquent\Relations\HasMany;

class Inscription extends Model
{
    use HasFactory;

    protected $table = 'inscriptions';

    protected $fillable = [
        'formation_id', 'apprenant_id', 'inscrit_par',
        'date_inscription', 'statut', 'paiement_effectue','statut_paiement',
        'montant_paye'
    ];



    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class);
    }

    public function inscritPar()
    {
        return $this->belongsTo(User::class, 'inscrit_par');
    }
    // public function apprenant()
    // {
    //     return $this->belongsTo(Utilisateur::class, 'apprenant_id');
    // }

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }
}
