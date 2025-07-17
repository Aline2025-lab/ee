<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReponseApprenant extends Model
{
    use HasFactory;

    protected $table = 'reponses_apprenant';

    protected $fillable = [
        'tentative_id',
        'question_id',
        'reponse_texte',
        'reponse_ids',
        'est_correcte',
    ];

    //Gère automatiquement la conversion de la colonne JSON en tableau PHP et vice-versa.
    protected $casts = [
        'reponse_ids' => 'array',
        'est_correcte' => 'boolean',
    ];

    //Une réponse appartient à une tentative.
    public function tentative(): BelongsTo
    {
        return $this->belongsTo(Tentative::class);
    }

    //Une réponse est liée à une question.
     
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
    
}
