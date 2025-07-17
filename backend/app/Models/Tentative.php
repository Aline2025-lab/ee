<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tentative extends Model
{
    use HasFactory;

    protected $table = 'tentatives';
    
    protected $fillable = [
        'examen_id',
        'apprenant_id',
        'statut',
        'score',
        'heure_debut',
        'heure_fin',
    ];

    //Une tentative concerne un seul examen.
    
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }

    //Une tentative est effectuée par un seul apprenant.
     
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }
    
    // Une tentative contient plusieurs réponses de l'apprenant.
    
    public function reponses(): HasMany
    {
        return $this->hasMany(ReponseApprenant::class);
    }
}
