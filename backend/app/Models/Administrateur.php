<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
//use Illuminate\Database\Eloquent\Relations\HasMany;

class Administrateur extends Model
{
    use HasFactory;

    protected $table = 'administrateurs';
    
    protected $fillable = [
        'matriculeAD',
        'utilisateur_id',
        'date_derniere_action'
    ];

    public function utilisateur() {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
