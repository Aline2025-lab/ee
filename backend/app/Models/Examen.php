<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Examen extends Model
{
    use HasFactory;

    protected $table = 'examens';
    
    protected $fillable = [
        'titre',
        'type',
        'description',
        'statut',
        'note_sur',
        'examinable_id',   // Important pour la relation polymorphe
        'examinable_type', // Important pour la relation polymorphe
    ];

    //La relation polymorphe qui permet de récupérer le parent (Formation, Module ou Leçon).
    public function examinable(): MorphTo
    {
        return $this->morphTo();
    }

    public function formateur() {
        return $this->belongsTo(Formateur::class);
    }

    public function classe() {
        return $this->belongsTo(Classe::class);
    }

    //Un examen est composé de plusieurs questions.
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }


    // Un examen peut avoir plusieurs tentatives par les apprenants.
    public function tentatives(): HasMany
    {
        return $this->hasMany(Tentative::class);
    }
}
