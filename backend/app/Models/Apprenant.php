<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apprenant extends Model
{
    use HasFactory;

    protected $table = 'apprenants';

    protected $fillable = [
        'matriculeAP',
        'niveau_etude',
        'statut_actuel',
        'utilisateur_id',
        'parent_id',
        'derniere_connexion'
    ];

    public function utilisateur() {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }


    public function seances()
    {
        return $this->belongsToMany(Seance::class, 'presence')
                    ->withPivot('est_present', 'justificatif', 'remarque')
                    ->withTimestamps();
    }

    public function formations() {
        return $this->belongsToMany(Formation::class, 'inscriptions', 'apprenant_id', 'formation_id')
               ->withPivot(['formateur_id', 'date_inscription', 'statut'])
               ->withTimestamps();
    }

    public function inscriptions() {
        return $this->hasMany(Inscription::class);
    }

    // public function parent() {
    //     return $this->belongsTo(Parents::class, 'parent_id');
    // }


    public function tentatives(): HasMany
    {
        return $this->hasMany(Tentative::class);
    }

    public function user(): BelongsTo
    {
        // On suppose que la table 'apprenants' a une colonne 'user_id'
        // qui est la clé étrangère vers la table 'users'.
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function enfants()
    {
        return $this->hasMany(\App\Models\Apprenant::class, 'parent_id', 'utilisateur_id');

    }
    public function parent()
    {
        return $this->belongsTo(\App\Models\Parents::class, 'parent_id', 'utilisateur_id');
    }
}

