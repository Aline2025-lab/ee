<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\ExamenImport;
use App\Models\Module;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;

class ImportController extends Controller
{
    public function importExamen(Request $request, Module $module)
    {
        $this->authorizeRoles(['formateur', 'superviseur', 'administrateur']);

        $request->validate([
            'fichier' => 'required|file|mimes:csv,xlsx,xls',
            'titre' => 'required|string|max:255',
            'type' => 'required|in:test,evaluation'
        ]);

        try {
            $examen = DB::transaction(function() use ($request, $module) {
                // Créer l'examen parent
                $examen = $module->examens()->create([
                    'titre' => $request->input('titre'),
                    'type' => $request->input('type'),
                    'description' => 'Examen importé depuis un fichier.',
                    'statut' => 'brouillon',
                ]);

                // Lire le fichier et créer les questions
                $rows = Excel::toCollection(new ExamenImport, $request->file('fichier'))->first();


                $totalPoints = 0;

                foreach ($rows as $row) {
                    // Validation simple de la ligne
                    if (empty($row['enonce']) || empty($row['type'])) continue;

                    $totalPoints += (int)$row['points'];

                    $question = $examen->questions()->create([
                        'enonce' => $row['enonce'],
                        'type' => $row['type'],
                        'points' => (int)$row['points'] ?: 1,
                    ]);

                    // Gérer les options et les bonnes réponses
                    if ($row['type'] === 'choix_unique' || $row['type'] === 'choix_multiple') {
                        $correctAnswers = explode(',', $row['reponse_correcte']);

                        for ($i = 1; $i <= 6; $i++) { // On suppose jusqu'à 6 options
                            if (!empty($row["option{$i}"])) {
                                $question->options()->create([
                                    'texte_option' => $row["option{$i}"],
                                    'est_correcte' => in_array($i, $correctAnswers)
                                ]);
                            }
                        }
                    }
                }

                // Mettre à jour la note totale de l'examen
                $examen->update(['note_sur' => $totalPoints]);

                return $examen;
            });

            return response()->json([
                'message' => 'Examen importé et créé avec succès !',
                'data' => $examen->load('questions.options')
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'importation.', 'error' => $e->getMessage()], 500);
        }
    }
}
