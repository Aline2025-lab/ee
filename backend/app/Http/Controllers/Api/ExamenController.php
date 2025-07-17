<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Examen;
use Illuminate\Http\Request;
use App\Models\Module;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
/**
 *
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Serveur de l'API"
 * )
 *
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer"
 * )
 *
 * @OA\Tag(
 *     name="Examens",
 *     description="Endpoints pour la gestion des examens"
 * )
 */



class ExamenController extends Controller
{


    public function indexForModule(Module $module)
    {
        $this->authorizeRoles(['administrateur', 'superviseur', 'formateur', 'apprenant']);

        // On récupère uniquement les examens 'publiés' ou 'archivés' pour la liste générale.
        $examens = $module->examens()
                        ->whereIn('statut', ['publié', 'archivé'])
                        ->latest()
                        ->get();

        return response()->json($examens);
    }


    /**
     * Store a newly created examen for a specific module.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Module  $module
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * @OA\Post(
     *      path="/api/modules/{module}/examens",
     *      operationId="storeExamenForModule",
     *      tags={"Examens"},
     *      summary="Créer un nouvel examen pour un module spécifique",
     *      description="Crée un examen complet avec ses questions et options de réponse, et l'associe à un module.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="module",
     *          in="path",
     *          required=true,
     *          description="ID du module auquel l'examen sera rattaché",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Données de l'examen à créer",
     *          @OA\JsonContent(
     *              required={"titre", "type", "note_sur", "questions"},
     *              @OA\Property(property="titre", type="string", example="Évaluation finale sur Laravel"),
     *              @OA\Property(property="type", type="string", enum={"test", "evaluation"}, example="evaluation"),
     *              @OA\Property(property="description", type="string", example="Cette évaluation couvre les concepts de base."),
     *              @OA\Property(property="statut", type="string", enum={"brouillon", "publié"}, example="publié"),
     *              @OA\Property(property="note_sur", type="integer", example=20),
     *              @OA\Property(
     *                  property="questions",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      required={"enonce", "type", "points"},
     *                      @OA\Property(property="enonce", type="string", example="Quelle commande crée un contrôleur ?"),
     *                      @OA\Property(property="type", type="string", enum={"choix_unique", "choix_multiple", "texte_libre"}),
     *                      @OA\Property(property="points", type="integer", example=5),
     *                      @OA\Property(
     *                          property="options",
     *                          type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              required={"texte_option", "est_correcte"},
     *                              @OA\Property(property="texte_option", type="string", example="php artisan make:controller"),
     *                              @OA\Property(property="est_correcte", type="boolean", example=true)
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Examen créé avec succès",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Examen créé avec succès !"),
     *              @OA\Property(property="data", type="object", ref="#/components/schemas/Examen")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Non authentifié"),
     *      @OA\Response(response=403, description="Accès non autorisé"),
     *      @OA\Response(response=422, description="Erreur de validation des données")
     * )
     */
    public function storeForModule(Request $request, Module $module)
    {
         // Sécurisation de l'endpoint
        $this->authorizeRoles(['administrateur', 'superviseur']);
        //1. Validation très détaillée de la requête
        $validatedData = $request->validate([
            'titre' => 'required|string|max:255',
            'type' => ['required', Rule::in(['test', 'evaluation'])],
            'description' => 'nullable|string',
            'statut' => ['nullable', Rule::in(['brouillon', 'publié','archive'])],
            'note_sur' => 'required|integer|min:1',

            'questions' => 'required|array|min:1',
            'questions.*.enonce' => 'required|string',
            'questions.*.type' => ['required', Rule::in(['choix_unique', 'choix_multiple', 'texte_libre', 'glisser_deposer'])],
            'questions.*.points' => 'required|integer|min:1',

            // Les options sont requises seulement si la question est de type QCM
            'questions.*.options' => 'required_if:questions.*.type,choix_unique,choix_multiple|array|min:2',
            'questions.*.options.*.texte_option' => 'required|string',
            'questions.*.options.*.est_correcte' => 'required|boolean',
        ]);

        // 2. Utilisation d'une transaction pour la sécurité des données
        try {
            $examen = DB::transaction(function () use ($validatedData, $module) {

                // 3. Créer l'examen et l'associer au module
                $examen = $module->examens()->create([
                    'titre' => $validatedData['titre'],
                    'type' => $validatedData['type'],
                    'description' => $validatedData['description'] ?? null,
                    'statut' => $validatedData['statut'] ?? 'brouillon',
                    'note_sur' => $validatedData['note_sur'],
                ]);

                // 4. Parcourir et créer chaque question
                foreach ($validatedData['questions'] as $questionData) {
                    $question = $examen->questions()->create([
                        'enonce' => $questionData['enonce'],
                        'type' => $questionData['type'],
                        'points' => $questionData['points'],
                    ]);

                    // 5. Si des options existent pour cette question, les créer
                    if (isset($questionData['options'])) {
                        $question->options()->createMany($questionData['options']);
                    }
                }

                // Retourner l'examen complet pour le bloc transaction
                return $examen;
            });

            // 6. Charger les relations pour une réponse complète et renvoyer le JSON
            $examen->load('questions.options');

            return response()->json([
                'message' => 'Examen créé avec succès !',
                'data' => $examen,
            ], 201); // 201 Created

        } catch (\Exception $e) {
            // En cas d'erreur, tout est annulé par la transaction
            return response()->json([
                'message' => 'Erreur lors de la création de l\'examen.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Examen $examen)
    {
        //
    }



    public function update(Request $request, Examen $examen)
    {
        $this->authorizeRoles(['administrateur', 'superviseur']);

        $validatedData = $request->validate([
            'titre' => 'required|string|max:255',
            'type' => ['required', Rule::in(['test', 'evaluation'])],
            'description' => 'nullable|string',
            'statut' => ['required', Rule::in(['brouillon', 'publié'])],
            'note_sur' => 'required|integer|min:1',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer|exists:questions,id',
            'questions.*.enonce' => 'required|string',
            'questions.*.type' => ['required', Rule::in(['choix_unique', 'choix_multiple', 'texte_libre'])],
            'questions.*.points' => 'required|integer|min:1',
            'questions.*.options' => 'required_if:questions.*.type,choix_unique,choix_multiple|array|min:2',
            'questions.*.options.*.id' => 'nullable|integer|exists:options,id',
            'questions.*.options.*.texte_option' => 'required|string',
            'questions.*.options.*.est_correcte' => 'required|boolean',
        ]);

        try {
            DB::transaction(function () use ($validatedData, $examen) {
                $examen->update([
                    'titre' => $validatedData['titre'],
                    'type' => $validatedData['type'],
                    'description' => $validatedData['description'],
                    'statut' => $validatedData['statut'],
                    'note_sur' => $validatedData['note_sur'],
                ]);

                $incomingQuestionIds = [];
                foreach ($validatedData['questions'] as $questionData) {
                    $question = $examen->questions()->updateOrCreate(
                        ['id' => $questionData['id'] ?? null],
                        [
                            'enonce' => $questionData['enonce'],
                            'type' => $questionData['type'],
                            'points' => $questionData['points']
                        ]
                    );
                    $incomingQuestionIds[] = $question->id;

                    if (isset($questionData['options'])) {
                        $incomingOptionIds = [];
                        foreach ($questionData['options'] as $optionData) {
                            $option = $question->options()->updateOrCreate(
                                ['id' => $optionData['id'] ?? null],
                                [
                                    'texte_option' => $optionData['texte_option'],
                                    'est_correcte' => $optionData['est_correcte']
                                ]
                            );
                            $incomingOptionIds[] = $option->id;
                        }
                        // Supprime les options qui n'étaient pas dans la requête
                        $question->options()->whereNotIn('id', $incomingOptionIds)->delete();
                    }
                }
                // Supprime les questions qui n'étaient pas dans la requête
                $examen->questions()->whereNotIn('id', $incomingQuestionIds)->delete();
            });

            return response()->json([
                'message' => 'Examen mis à jour avec succès.',
                'data' => $examen->fresh()->load('questions.options') // Recharger pour avoir les données fraîches
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Examen $examen)
    {
        //
    }
}
