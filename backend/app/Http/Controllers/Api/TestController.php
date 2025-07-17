<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Examen;
use App\Models\Question;


class TestController extends Controller
{
    /**
     * Reçoit les réponses d'un test, les corrige et renvoie le résultat
     * sans rien sauvegarder de manière permanente.
     */
    public function checkAnswers(Request $request, Examen $examen)
    {
        // Sécurité 1 : S'assurer que c'est bien un 'test' et non une 'evaluation'
        if ($examen->type !== 'test') {
            return response()->json(['message' => 'Cette action est réservée aux tests d\'entraînement.'], 400);
        }

        // Sécurité 2 : S'assurer que le test est publié
        if ($examen->statut !== 'publié') {
            return response()->json(['message' => 'Ce test n\'est pas disponible.'], 403);
        }

        // Validation des réponses reçues
        $validatedData = $request->validate([
            'reponses' => 'present|array', // 'present' autorise un tableau vide
            'reponses.*.question_id' => 'required|integer|exists:questions,id',
            'reponses.*.reponse_ids' => 'nullable|array',
            'reponses.*.reponse_ids.*' => 'integer|exists:options,id',
            'reponses.*.reponse_texte' => 'nullable|string',
        ]);

        // Charger toutes les questions et options de l'examen en une seule fois
        $examen->load('questions.options');

        $results = [];
        $score = 0;
        $note_sur = $examen->questions->sum('points');

        // On boucle sur TOUTES les questions de l'examen original
        foreach ($examen->questions as $question) {
            // Trouver la réponse de l'utilisateur pour cette question
            $userResponse = collect($validatedData['reponses'])->firstWhere('question_id', $question->id);

            $isCorrect = false;
            $userAnswerIds = [];

            if ($userResponse) {
                // Logique de correction (la même que dans TentativeController)
                switch ($question->type) {
                    case 'choix_unique':
                    case 'choix_multiple':
                        $correctOptionIds = $question->options->where('est_correcte', true)->pluck('id')->sort()->values()->all();
                        $submittedOptionIds = collect($userResponse['reponse_ids'] ?? [])->sort()->values()->all();
                        $isCorrect = ($correctOptionIds === $submittedOptionIds);
                        $userAnswerIds = $submittedOptionIds;
                        break;
                    case 'texte_libre':
                        // Pour un test, on ne peut pas corriger le texte. On considère la réponse comme non notée.
                        $isCorrect = null;
                        break;
                }
            }

            if ($isCorrect === true) {
                $score += $question->points;
            }

            // On construit le tableau de résultat pour cette question
            $results[] = [
                'question_id' => $question->id,
                'enonce' => $question->enonce,
                'points' => $question->points,
                'est_correcte' => $isCorrect, // true, false, ou null (si pas répondu ou texte libre)
                'reponse_utilisateur' => $userAnswerIds, // Les ID cochés par l'utilisateur
                'bonnes_reponses' => $question->options->where('est_correcte', true)->pluck('id')->all(), // Les ID des bonnes réponses
            ];
        }

        // On ne sauvegarde RIEN en base de données. On renvoie juste le résultat.
        return response()->json([
            'message' => 'Correction du test terminée.',
            'score' => $score,
            'note_sur' => $note_sur,
            'resultats' => $results,
        ]);
    }
}
