<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Module;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Examen;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lecon extends Model
{
    use HasFactory;

    protected $table = 'lecons';

    protected $fillable = [
        'titre',
        'contenu',
        'duree_estimee',
        'ordre',
        'module_id',
        'lien_ressource',
    ];
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    //Récupère tous les examens associés à ce modèle (Formation, Module ou Leçon).
    public function examens(): MorphMany
    {
        return $this->morphMany(Examen::class, 'examinable');
    }
}
