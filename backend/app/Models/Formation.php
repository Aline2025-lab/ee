<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inscription;
use App\Models\Module;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Examen;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Formation extends Model
{
   use HasFactory;

    protected $table = 'formations';

    protected $fillable = [
        'nom_formation',
        'libelle_formation',
        'date_debutf',
        'date_finf',
        'nombre_seancef',
        'volume_horaire',
        'certifiante',
        'prix_certification',
        'prix',
        'statut',
        'objectif',
    ];

    //Récupère tous les examens associés à ce modèle (Formation, Module ou Leçon).
    public function examens(): MorphMany
    {
        return $this->morphMany(Examen::class, 'examinable');
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    public function apprenants() {
        return $this->belongsToMany(Apprenant::class, 'inscriptions', 'formation_id', 'apprenant_id')
               ->withPivot([ 'date_inscription', 'statut'])
               ->withTimestamps();
    }


    public function formateurs()
    {
        return $this->belongsToMany(Formateur::class, 'formation_formateur');

    }

    public function formateur()
    {
        return $this->belongsToMany(
            User::class,
            'formation_formateur',
            'formation_id',
            'formateur_id'
        )->withTimestamps();
    }
}
