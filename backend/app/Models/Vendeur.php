<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
//use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendeur extends Model
{
    use HasFactory;

    protected $table = 'vendeurs';
    
    protected $fillable = [
        'matriculeVE',
        'utilisateur_id',
        'date_derniere_connexion'
        
    ];

    public function utilisateur() {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
