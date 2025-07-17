<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


// use App\Models\Formation;
// use App\Models\Formateur;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ClasseController extends Controller
{
    private function checkAdminOrSuperviseur()
    {
        $user = Auth::user();
        if (!in_array($user->role->libelle, ['superviseur', 'administrateur'])) {
            // On renvoie directement une réponse et on arrête l'exécution
            abort(403, 'Accès non autorisé. Seuls les administrateurs ou superviseurs sont permis.');
        }
    }


    public function verifierDisponibilite(Request $request, $id)
    {
        $classe = Classe::findOrFail($id);
        $request->validate([
            'date' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i',
        ]);

        $conflict = $classe->seances()->where('date', $request->date)
            ->where(function ($query) use ($request) {
                $query->whereBetween('heure_debut', [$request->heure_debut, $request->heure_fin])
                    ->orWhereBetween('heure_fin', [$request->heure_debut, $request->heure_fin]);
            })->exists();

        return response()->json(['disponible' => !$conflict]);
    }



    public function getCapaciteRestante($id, Request $request)
    {
        return response()->json(['capacite_restante' => 10]); // à implémenter
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Classe::with('formation')->get(); // Charge la relation formation
    }



    public function store(Request $request)
    {

       $this->checkAdminOrSuperviseur();

        $validated = $request->validate([
            'nom' => 'required|string',
            'capacite' => 'required|integer|min:1',
            'localisation' => 'nullable|string',
            'description' => 'nullable|string',
            'formation_id' => 'required|exists:formations,id'
        ]);

        $classe = Classe::create($validated);
        return response()->json($classe, 201);
    }




    public function update(Request $request, $id)
    {
        $this->checkAdminOrSuperviseur();
        $classe = Classe::findOrFail($id);
        $classe->update($request->only(['nom', 'capacite', 'localisation', 'description']));
        return response()->json($classe);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Classe $classe)
    {
        //
    }
}
