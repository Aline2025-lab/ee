<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
//use Illuminate\Database\Eloquent\Relations\HasMany;

class Superviseur extends Model
{
    use HasFactory;

    protected $table = 'superviseurs';
    
    protected $fillable = [
        'matriculeSU',
        'utilisateur_id',
        'date_derniere_action'
    ];
    

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
