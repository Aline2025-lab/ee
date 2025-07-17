<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Presence;
use Illuminate\Http\Request;


use App\Models\Seance;
use App\Models\Apprenant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class PresenceController extends Controller
{

    // Marquer la présence d’un apprenant à une séance.
    public function marquerPresence(Request $request)
    {
        // 1. Vérification du rôle (votre code est parfait)
        $user = Auth::user();
        if ($user->role->libelle !== 'formateur') {
            return response()->json([
                'message' => 'Accès interdit : seuls les formateurs peuvent gérer les présences.'
            ], 403);
        }

        // 2. Validation des entrées
        $validator = Validator::make($request->all(), [
            // Assurez-vous que 'apprenant_id' pointe vers la bonne table (probablement 'users')
            'apprenant_id' => 'required|exists:apprenants,id',
            'seance_id'    => 'required|exists:seances,id',
            'est_present'  => 'required|boolean',
            'justificatif' => 'nullable|string|max:255',
            'remarque'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // 3. Vérifications de sécurité et de logique métier
        $seance = Seance::find($validated['seance_id']);

        // Le formateur authentifié est-il bien celui de la séance ? (Sécurité +)
        // NOTE : Adaptez 'formateur_id' si le champ a un autre nom dans votre table 'seances'
        if ($seance->formateur_id !== $user->id) { // Supposant que l'id du formateur est sur l'utilisateur
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à gérer cette séance.'], 403);
        }

        // La séance est-elle bien en cours ?
        if ($seance->statut !== 'En cours') {
            return response()->json([
                'message' => 'La présence ne peut être modifiée que pour une séance qui est "En cours".',
                'statut_actuel' => $seance->statut
            ], 409); // 409 Conflict
        }

        // 4. Mise à jour de la présence (on ne crée plus, on trouve et on met à jour)
        try {
            $presence = Presence::where('seance_id', $validated['seance_id'])
                                ->where('apprenant_id', $validated['apprenant_id'])
                                ->firstOrFail(); // Lance une erreur si la fiche n'a pas été créée par demarrerSeance

            $presence->update([
                'est_present'  => $validated['est_present'],
                'justificatif' => $validated['justificatif'] ?? $presence->justificatif,
                'remarque'     => $validated['remarque'] ?? $presence->remarque,
            ]);

            return response()->json([
                'message' => 'Présence mise à jour avec succès.',
                'data' => $presence
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Ce cas ne devrait pas arriver si demarrerSeance a bien fonctionné
            return response()->json([
                'message' => 'Erreur : Impossible de trouver la fiche de présence pour cet apprenant et cette séance.',
            ], 404);
        }
    }

    //Justifier une absence avec un fichier ou un texte.
    public function justifierAbsence(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'apprenant_id' => 'required|exists:apprenants,id',
            'seance_id' => 'required|exists:seances,id',
            'justificatif' => 'nullable|string',
            'remarque' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $presence = Presence::where('apprenant_id', $request->apprenant_id)
            ->where('seance_id', $request->seance_id)
            ->first();

        if (!$presence) {
            return response()->json(['message' => 'Présence non trouvée'], 404);
        }

        $presence->update([
            'justificatif' => $request->justificatif,
            'remarque' => $request->remarque
        ]);

        return response()->json($presence, 200);
    }

    //Liste des présences pour une séance.
    public function listePresenceParSeance($seance_id)
    {
        $presences = Presence::where('seance_id', $seance_id)->get();
        return response()->json($presences);
    }

    //Liste des présences pour un apprenant.
    public function listePresenceParApprenant($apprenant_id)
    {
        $apprenant = Apprenant::with('seances')->findOrFail($apprenant_id);
        return response()->json($apprenant->seances);
    }
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
    public function show(Presence $presence)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Presence $presence)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Presence $presence)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Presence $presence)
    {
        //
    }

    public function presencesParDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 400);

        $seances = Seance::whereDate('date', $request->date)->pluck('id');
        $presences = Presence::whereIn('seance_id', $seances)->with(['seance', 'apprenant'])->get();

        return response()->json($presences);
    }

    public function exporterCSV($seance_id)
    {
        $presences = Presence::with('apprenant')
            ->where('seance_id', $seance_id)
            ->get();

        $seance = Seance::findOrFail($seance_id);

        $csvData = "Nom,Prenom,Présence,Justification\n";
        foreach ($presences as $p) {
            $csvData .= $p->apprenant->nom . "," . $p->apprenant->prenom . "," . ($p->est_present ? "Oui" : "Non") . "," . ($p->justification ?? '') . "\n";
        }

        $fileName = 'presence_seance_' . $seance_id . '.csv';
        Storage::disk('local')->put($fileName, $csvData);

        return response()->download(storage_path("app/" . $fileName))->deleteFileAfterSend();
    }

    public function exporterPDF($seance_id)
    {
        $seance = Seance::with(['formation', 'classe', 'formateur', 'presences.apprenant'])->findOrFail($seance_id);

        $pdf = Pdf::loadView('pdf.presence', compact('seance'));
        return $pdf->download('feuille_presence_' . $seance->titre . '.pdf');
    }
}
