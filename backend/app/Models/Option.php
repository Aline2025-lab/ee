<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    use HasFactory;
    
    protected $table = 'options';

    protected $fillable = [
        'question_id',
        'texte_option',
        'est_correcte',
    ];

    // Une option de réponse appartient à une seule question.
     
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
