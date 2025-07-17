<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Lecon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeconController extends Controller
{

    private function checkAdminOrSuperviseur()
    {
        $user = Auth::user();
        if (!in_array($user->role->libelle, ['superviseur', 'administrateur'])) {
            // On renvoie directement une réponse et on arrête l'exécution
            abort(403, 'Accès non autorisé. Seuls les administrateurs ou superviseurs sont permis.');
        }
    }

    public function index()
    {
        //
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Lecon $lecon)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lecon $lecon)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lecon $lecon)
    {
        //
    }
}
