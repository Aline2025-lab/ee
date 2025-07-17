<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
//use Illuminate\Database\Eloquent\Relations\HasMany;

class Presence extends Model
{
    use HasFactory;

    protected $table = 'presences';

    protected $fillable = [
        'apprenant_id',
        'seance_id',
        'est_present',
        'justificatif',
        'remarque'
    ];

    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class);
    }

    public function seance()
    {
        return $this->belongsTo(Seance::class);
    }


    

}
