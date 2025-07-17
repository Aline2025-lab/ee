<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Apprenant;
use App\Models\Formation;


class User extends Authenticatable
{
    use HasFactory, Notifiable;

    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $table = 'utilisateurs';

    protected $fillable = [
        'nom',
        'prenom',
        'genre',
        'date_naissance',
        'email',
        'password',
        'role_id',
        'login',
        'login',
        'is_active',
        'verification_code',
        'email_verified',
        'doit_changer_mot_de_passe',
        'photo_profil'
    ];

    protected $casts = [
        'verrouille_jusqua' => 'datetime',
    ];


    protected $hidden = [
        'password',
        //'mdp',
        'remember_token',
    ];




    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function formateur()
    {
        return $this->hasOne(Formateur::class, 'utilisateur_id');
    }

    public function apprenant()
    {
        return $this->hasOne(Apprenant::class, 'utilisateur_id');
    }

    public function superviseur()
    {
        return $this->hasOne(Superviseur::class, 'utilisateur_id');
    }

    public function administrateur()
    {
        return $this->hasOne(Administrateur::class, 'utilisateur_id');
    }

    public function parents()
    {
        return $this->hasOne(Parents::class, 'utilisateur_id');
    }

    public function caissier()
    {
        return $this->hasOne(Caissier::class, 'utilisateur_id');
    }

    public function auditeur()
    {
        return $this->hasOne(Auditeur::class, 'utilisateur_id');
    }

    public function vendeur()
    {
        return $this->hasOne(Vendeur::class, 'utilisateur_id');
    }

    public function inscriptionsFormateur()
    {
        return $this->hasMany(Inscription::class, 'formateur_id');
    }

    public function formations()
    {
        return $this->belongsToMany(
            Formation::class,
            'formation_formateur',   // nom de la table pivot
            'formateur_id',          // clé étrangère de ce modèle dans la pivot
            'formation_id'           // clé étrangère du modèle Formation dans la pivot
        )->withTimestamps();
    }


}
