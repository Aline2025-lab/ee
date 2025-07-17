<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Formation;
use App\Models\Lecon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Examen;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Module extends Model
{
    use HasFactory;

    protected $table = 'modules';

    protected $fillable = [
        'nom', 
        'description', 
        'formation_id'
    ];

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }

    public function lecons()
    {
        return $this->hasMany(Lecon::class);
    }

    // Récupère tous les examens associés à ce modèle (Formation, Module ou Leçon).
 
    public function examens(): MorphMany
    {
        return $this->morphMany(Examen::class, 'examinable');
    }
}
