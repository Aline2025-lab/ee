<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{

    // private function checkAdminOrSuperviseur()
    // {
    //     $user = auth()->user();
    //     if (!in_array($user->role->libelle, ['superviseur', 'administrateur'])) {
    //         // On renvoie directement une réponse et on arrête l'exécution
    //         abort(403, 'Accès non autorisé. Seuls les administrateurs ou superviseurs sont permis.');
    //     }
    // }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
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
    public function show(Module $module)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Module $module)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Module $module)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Module $module)
    {
        //
    }
}
