<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parents extends Model
{
    use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'matriculePA',
        'date_derniere_action',
        'utilisateur_id'
    ];

    public function utilisateur() {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }


    public function enfants()
    {
        return $this->hasMany(\App\Models\Apprenant::class, 'parent_id', 'utilisateur_id');
    }
}
