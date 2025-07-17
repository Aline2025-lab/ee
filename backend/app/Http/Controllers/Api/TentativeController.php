<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Examen;
use Illuminate\Support\Facades\Auth;
use App\Models\Tentative;
use App\Models\Question; 
use Illuminate\Support\Facades\DB;

class TentativeController extends Controller
{
   /**
     * Démarre une nouvelle tentative d'examen pour l'apprenant connecté.
     */
    // Dans app/Http/Controllers/Api/TentativeController.php

    public function start(Request $request, Examen $examen)
    {
        $this->authorizeRoles(['apprenant']);
        
        // Si l'utilisateur connecté n'a pas de profil 'apprenant' associé, c'est une erreur.
        if (!Auth::user()->apprenant) {
            return response()->json(['message' => 'Profil apprenant non trouvé pour cet utilisateur.'], 404);
        }
        
        if ($examen->statut !== 'publié') {
            return response()->json(['message' => 'Cet examen n\'est pas disponible.'], 403);
        }

        // On utilise directement la relation 'apprenant' sur l'utilisateur connecté
        // pour ensuite appeler la relation 'tentatives' sur l'objet Apprenant.
        $existingAttempt = Auth::user()->apprenant->tentatives()
            ->where('examen_id', $examen->id)
            ->where('statut', 'en_cours')
            ->first();

        if ($existingAttempt) {
            return response()->json([
                'message' => 'Vous avez déjà une tentative en cours pour cet examen.',
                'tentative' => $existingAttempt
            ], 409);
        }
        
        // On crée la tentative en utilisant l'ID du profil apprenant
        $tentative = $examen->tentatives()->create([
            'apprenant_id' => Auth::user()->apprenant->id, // On prend l'ID du profil, pas de l'utilisateur
            'statut' => 'en_cours',
            'heure_debut' => now(),
        ]);
        
        return response()->json([
            'message' => 'Tentative démarrée avec succès.',
            'tentative_id' => $tentative->id,
            'examen' => $examen->load('questions.options') 
        ], 201);
    }



    /**
     * Soumet les réponses pour une tentative, la corrige et calcule le score.
     */
    public function submit(Request $request, Tentative $tentative)
    {
         // Sécurité : on vérifie que l'ID de l'apprenant dans la tentative
        // correspond bien à l'ID du profil apprenant de l'utilisateur qui fait la requête.
        if (!Auth::user()->apprenant || $tentative->apprenant_id !== Auth::user()->apprenant->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
        // Sécurité : vérifier que l'utilisateur connecté est bien le propriétaire de la tentative
        // if ($tentative->apprenant_id !== Auth::id()) {
        //     return response()->json(['message' => 'Action non autorisée.'], 403);
        // }

        // Vérification : la tentative n'a-t-elle pas déjà été soumise ?
        if ($tentative->statut !== 'en_cours') {
            return response()->json(['message' => 'Cette tentative a déjà été soumise.'], 400);
        }

        // Validation des réponses envoyées
        $validatedData = $request->validate([
            'reponses' => 'required|array',
            'reponses.*.question_id' => 'required|integer|exists:questions,id',
            'reponses.*.reponse_texte' => 'nullable|string', // Pour les questions à texte libre
            'reponses.*.reponse_ids' => 'nullable|array', // Pour les QCM
            'reponses.*.reponse_ids.*' => 'integer|exists:options,id',
        ]);

        $totalScore = 0;

        // On utilise une transaction pour s'assurer que toutes les réponses sont enregistrées
        DB::transaction(function () use ($validatedData, $tentative, &$totalScore) {
            
            $questions = Question::with('options')->findMany(array_column($validatedData['reponses'], 'question_id'));

            foreach ($validatedData['reponses'] as $reponseData) {
                $question = $questions->find($reponseData['question_id']);
                if (!$question) continue;

                $isCorrect = false;

                // Logique de correction
                switch ($question->type) {
                    case 'choix_unique':
                    case 'choix_multiple':
                        $correctOptionIds = $question->options->where('est_correcte', true)->pluck('id')->sort()->values()->all();
                        $submittedOptionIds = collect($reponseData['reponse_ids'] ?? [])->sort()->values()->all();
                        $isCorrect = ($correctOptionIds === $submittedOptionIds);
                        break;
                    case 'texte_libre':
                        // La correction des textes libres est souvent manuelle. 
                        // Pour l'instant, on la marque comme "en attente de correction".
                        $isCorrect = null; 
                        break;
                }

                // Enregistrer la réponse de l'apprenant dans la BDD
                $tentative->reponses()->create([
                    'question_id' => $question->id,
                    'reponse_texte' => $reponseData['reponse_texte'] ?? null,
                    'reponse_ids' => $reponseData['reponse_ids'] ?? null,
                    'est_correcte' => $isCorrect,
                ]);

                if ($isCorrect === true) {
                    $totalScore += $question->points;
                }
            }

            // Mettre à jour la tentative avec le score final et le statut
            $tentative->update([
                'statut' => 'terminee',
                'score' => $totalScore,
                'heure_fin' => now(),
            ]);
        });
        
        // (Optionnel) Envoyer un email de notification ici
        // Mail::to(Auth::user()->email)->send(new ResultatExamenMail($tentative));

        return response()->json([
            'message' => 'Examen soumis et corrigé avec succès !',
            'resultat' => [
                'score' => $tentative->score,
                'note_sur' => $tentative->examen->note_sur,
                'statut' => $tentative->statut,
            ]
        ]);
    }
    

    /**
    * @OA\Get(
    *     path="/api/evaluations/{examen}/tentatives",
    *     summary="Lister toutes les tentatives pour une évaluation spécifique.",
    *     tags={"Évaluations - Suivi"},
    *     // ... reste de la documentation Swagger
    * )
    */
    public function indexForExamen(Request $request, Examen $examen)
    {
        // Autorisation : Seuls les formateurs, superviseurs, admins peuvent voir ça
        $this->authorizeRoles(['formateur', 'superviseur', 'administrateur']);

        // S'assurer qu'on consulte bien les résultats d'une 'evaluation'
        if ($examen->type !== 'evaluation') {
            return response()->json(['message' => 'Le suivi n\'est disponible que pour les évaluations.'], 400);
        }

        // On récupère les tentatives en chargeant les informations de l'apprenant associé
        $tentatives = $examen->tentatives()
                            ->with('apprenant.user') // Charge la tentative -> son apprenant -> son utilisateur (pour le nom, etc.)
                            ->latest('heure_fin') // Trie par date de fin la plus récente
                            ->paginate(15); // On pagine pour ne pas tout charger d'un coup

        return response()->json($tentatives);
    }

    /**
 * @OA\Get(
 *     path="/api/evaluations/{examen}/statistiques",
 *     summary="Obtenir des statistiques agrégées pour une évaluation.",
 *     tags={"Évaluations - Suivi"},
 *     // ... reste de la documentation Swagger
 * )
 */
public function getStatistiques(Examen $examen)
{
    $this->authorizeRoles(['formateur', 'superviseur', 'administrateur']);
    
    if ($examen->type !== 'evaluation') {
        return response()->json(['message' => 'Les statistiques ne sont disponibles que pour les évaluations.'], 400);
    }
    
    $tentatives = $examen->tentatives()->where('statut', 'terminee');

    // On utilise les fonctions d'agrégation de la base de données, c'est très performant
    $stats = [
        'titre_evaluation' => $examen->titre,
        'note_sur' => $examen->note_sur,
        'nombre_participants' => $tentatives->count(),
        'score_moyen' => round($tentatives->avg('score'), 2),
        'score_max' => $tentatives->max('score'),
        'score_min' => $tentatives->min('score'),
        'taux_reussite' => 0 // On calcule ce qui suit
    ];

    // Calcul du taux de réussite (on considère que la moyenne est 10/20 ou 50%)
    if ($stats['nombre_participants'] > 0) {
        $seuilReussite = $examen->note_sur / 2;
        $nombreReussites = $examen->tentatives()->where('statut', 'terminee')->where('score', '>=', $seuilReussite)->count();
        $stats['taux_reussite'] = round(($nombreReussites / $stats['nombre_participants']) * 100, 2);
    }
    
    return response()->json($stats);
}

}
