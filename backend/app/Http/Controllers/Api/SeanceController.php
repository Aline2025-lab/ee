<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seance;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


use App\Models\Classe;
use App\Models\Formation;
use App\Models\Formateur;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Presence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SeanceNotification;


class SeanceController extends Controller
{
    private function checkAdminOrSuperviseur()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user || !in_array($user->role->libelle, ['superviseur', 'administrateur'])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403)->send();
        }
    }

     // Affiche la liste des séances.
    public function index()
    {
        $seances = Seance::with(['formation:id,nom_formation','formateur:matriculeAD,specialite', 'formateur.utilisateur:nom,prenom'])->latest()->paginate(15);
        return response()->json($seances);
        //return response()->json(Seance::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $this->checkAdminOrSuperviseur();
        $validated = Validator::make($request->all(), [
            'titre' => 'required|string',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'formateur_id' => 'required|exists:formateurs,id',
            'formation_id' => 'required|exists:formations,id',
            'classe_id' => 'nullable|exists:classes,id',
            'type_seance' => 'nullable|string',
            'statut' => 'nullable|in:Planifiée,Confirmée,Annulée,Terminée,En cours'
        ])->validate();

        $seance = Seance::create($validated);
        return response()->json($seance->load(['formation', 'formateur']), 201);
    }

    //Affiche une séance spécifique.
    public function show(Seance $seance)
    {
        return response()->json($seance->load(['formation', 'formateur', 'presences.apprenant']));
    }

    public function update(Request $request, $id)
    {
        $this->checkAdminOrSuperviseur();
        $validated = $request->validate([
            'titre' => 'required|string',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'formateur_id' => 'required|exists:formateurs,id',
            'classe_id' => 'nullable|exists:classes,id',
            'type_seance' => 'nullable|string',
            'statut' => 'nullable|in:Planifiée,Confirmée,Annulée,Terminée,En cours'
        ]);

        $seance->update($validated);
        return response()->json($seance);

    }




    public function annulerSeance(Request $request, $id)
    {
        $this->checkAdminOrSuperviseur();

        $seance = Seance::findOrFail($id);
        $seance->update(['statut' => 'Annulée']);
        return response()->json(['message' => 'Séance annulée']);
    }

    public function demarrerSeance($id)
    {
       /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->role->libelle !== 'formateur' || $user->id !== $seance->formateur_id) {
            return response()->json(['message' => 'Accès interdit. Vous n\'êtes pas le formateur de cette séance.'], 403);
        }

        // On charge la séance AVEC ses relations pour être plus efficace (Eager Loading)
        $seance = Seance::with('formation.apprenants.utilisateur', 'presences')->findOrFail($id);


        // Vérification supplémentaire : la séance n'est-elle pas déjà en cours ou terminée ?
        if ($seance->statut !== 'Planifiée') { // Assurez-vous que le statut correspond
            return response()->json([
                'message' => 'Cette séance est déjà "' . $seance->statut . '" et ne peut être démarrée.',
            ], 409);
        }
        // 1. On construit les objets DateTime complets pour le début et la fin de la séance
        $debutProgramme = Carbon::parse($seance->date->toDateString() . ' ' . $seance->heure_debut->toTimeString());
        $finProgramme = Carbon::parse($seance->date->toDateString() . ' ' . $seance->heure_fin->toTimeString());

        // 2. On récupère l'heure actuelle
        $maintenant = now();

        // 3. On vérifie si l'heure actuelle est dans l'intervalle
        // Le troisième argument 'true' inclut les bornes (on peut démarrer à l'heure pile)
        if (!$maintenant->isBetween($debutProgramme, $finProgramme, true)) {
            return response()->json([
                'message' => 'Impossible de démarrer la séance. L\'heure actuelle est en dehors de l\'intervalle programmé.',
                'heure_actuelle' => $maintenant->format('H:i'),
                'intervalle_programme' => $debutProgramme->format('H:i') . ' - ' . $finProgramme->format('H:i'),
            ], 409); // 409 Conflict est un bon code HTTP pour ce cas
        }


        // On ne génère la liste que si elle n'existe pas déjà
        if ($seance->presences->isEmpty()) {

            // On récupère la collection des apprenants de la formation
            $apprenants = $seance->formation->apprenants;

            if ($apprenants->isNotEmpty()) {
                $donneesPresence = [];
                $now = now();

                foreach ($apprenants as $apprenant) {
                    $donneesPresence[] = [
                        'seance_id' => $seance->id,
                        'apprenant_id' => $apprenant->id,
                        // Valeurs par défaut
                        'est_present' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // On utilise une insertion de masse pour être très performant
                DB::table('presences')->insert($donneesPresence);
            }
        }



        // Si on arrive ici, tout est bon, on peut démarrer la séance.
        $seance->update(['statut' => 'En cours']);

        // On recharge la séance avec la liste de présence fraîchement créée pour la réponse
        $seance->load('presences.apprenant');

        return response()->json([
            'message' => 'Séance démarrée avec succès. La feuille de présence a été générée.',
            'data' => $seance
        ]);
    }

    public function terminerSeance($id)
    {

        $user = Auth::user();
        if ($user->role->libelle !== 'formateur') {
            return response()->json([
                'message' => 'Accès interdit : seuls les formateurs peuvent terminer les séances.'
            ], 403);
        }

        $seance = Seance::findOrFail($id);
        $seance->update(['statut' => 'Terminée']);
        return response()->json(['message' => 'Séance terminée']);
    }

    public function notifierParticipants(Request $request, Seance $seance)
    {
        // 1. Validation du message
        $validated = $request->validate([
            'message' => 'required|string|min:5|max:500'
        ]);
        $message = $validated['message'];

        // 2. Sécurité : seul le formateur de la séance peut notifier
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->role->libelle !== 'formateur' || $user->id !== $seance->formateur_id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        // 3. Récupérer les participants
        $participants = $seance->formation->apprenants;

        // 4. Envoyer la notification
        if ($participants->isNotEmpty()) {
            // La façade Notification est parfaite pour envoyer à une collection d'utilisateurs
            Notification::send($participants, new SeanceNotification($seance, $message));
        } else {
            return response()->json(['message' => 'Aucun participant à notifier pour cette séance.'], 404);
        }

        // 5. Retourner une réponse de succès
        return response()->json(['message' => 'Notification envoyée avec succès aux ' . $participants->count() . ' participant(s).']);
    }

    public function genererFeuillePresence($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->role->libelle !== 'formateur' || $user->id !== $seance->formateur_id) {
            return response()->json(['message' => 'Accès interdit. Vous n\'êtes pas le formateur de cette séance.'], 403);
        }

        $seance->load('formation.formateur', 'presences.apprenant');
        $pdf = PDF::loadView('pdf.feuille_presence', ['seance' => $seance]);
        $nomFichier = 'Feuille-Presence-' . $seance->formation->nom . '-' . Carbon::parse($seance->date)->format('Y-m-d') . '.pdf';

        return $pdf->download($nomFichier);

    }


    public function obtenirFormateur($id)
    {
        $this->checkAdminOrSuperviseur();

        $formateur = Seance::findOrFail($id)->formateur;
        return response()->json($formateur);
    }

    public function obtenirClasse($id)
    {
        $classe = Seance::findOrFail($id)->classe;
        return response()->json($classe);
    }



   public function destroy(Seance $seance)
    {
        $this->checkIsAdminOrSuperviseur();

        $seances = Seance::with(['formateur.utilisateur', 'formation', 'classe'])->get();
         $seance->delete();
        return response()->json($seances);
    }









    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Seance $seance)
    {
        //
    }


}
