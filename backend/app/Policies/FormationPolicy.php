<?php

namespace App\Policies;

use App\Models\Formation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FormationPolicy
{
    public function inscrire(User $user, Formation $formation)
    { \Log::info(auth()->user());
     return $user->role->libelle === 'formateur' && $formation->formateur_id === $user->id;
    

    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Formation $formation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Formation $formation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Formation $formation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Formation $formation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Formation $formation): bool
    {
        return false;
    }
}
