<?php

namespace App\Models\Schemas;

/**
 * @OA\Schema(
 *     schema="Examen",
 *     title="Examen",
 *     description="Modèle de données pour un examen",
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="titre", type="string", example="Évaluation sur Laravel"),
 *     @OA\Property(property="type", type="string", enum={"test", "evaluation"}),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="statut", type="string", enum={"brouillon", "publié", "archivé"}),
 *     @OA\Property(property="note_sur", type="integer"),
 *     @OA\Property(property="examinable_id", type="integer", description="ID du parent (Module, Formation...)"),
 *     @OA\Property(property="examinable_type", type="string", description="Type du parent (ex: App\Models\Module)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(
 *         property="questions",
 *         type="array",
 *         description="La liste des questions de l'examen",
 *         @OA\Items(ref="#/components/schemas/Question")
 *     )
 * )
 */
class ExamenSchema {}