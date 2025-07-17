<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Seance extends Model
{
    use HasFactory;

    protected $table = 'seances';

    protected $fillable = [
        'titre',
        'description',
        'date',
        'heure_debut',
        'heure_fin',
        'type_seance',
        'statut',
        'classe_id',
        'formation_id',
        'formateur_id'
    ];/**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',      // Traite 'date_seance' comme un objet Date
        'heure_debut' => 'datetime:H:i', // Traite 'heure_debut' comme un objet DateTime
        'heure_fin' => 'datetime:H:i',   // Traite 'heure_fin' comme un objet DateTime
    ];



    //Une séance appartient à une classe
    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    //Une séance appartient à une formation
    public function formation()
    {
        return $this->belongsTo(\App\Models\Formation::class, 'formation_id');
    }

    public function formateur()
    {
        return $this->belongsTo(\App\Models\Formateur::class, 'formateur_id');
    }

    public function presences()
    {
        return $this->hasMany(Presence::class);
    }

    public function apprenants()
    {
        return $this->belongsToMany(Apprenant::class, 'presence')
                    ->withPivot('est_present', 'justificatif', 'remarque')
                    ->withTimestamps();
    }

    /**
     * Obtenir tous les apprenants inscrits à cette séance via la table de présence.
     */
    public function apprenantsViaPresences()
    {
        return $this->belongsToMany(User::class, 'presences', 'seance_id', 'apprenant_id')
                    ->withPivot('est_present', 'justificatif', 'remarque')
                    ->withTimestamps();
    }
}
