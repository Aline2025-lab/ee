<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Question extends Model
{
    use HasFactory;
    
    protected $table = 'questions';

    protected $fillable = [
        'examen_id',
        'enonce',
        'type',
        'points',
        'ordre',
    ];

    //Une question appartient à un seul examen.
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }

    // Une question (de type QCM) a plusieurs options de réponse.
    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }
}
