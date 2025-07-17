<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
//use Illuminate\Database\Eloquent\Relations\HasMany;


class Formateur extends Model
{
    use HasFactory;

    protected $table = 'formateurs';

    protected $fillable = [
        'matriculeAD',
        'specialite',
        'CV',
        'date_derniere_action',
        'utilisateur_id'
    ];

    public function utilisateur() {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function formations()
    {
        return $this->belongsToMany(Formation::class, 'formation_formateur');
    }



}
