<?php

namespace App\Models\Schemas;

/**
 * @OA\Schema(
 *     schema="Question",
 *     title="Question",
 *     description="Modèle de données pour une question d'examen",
 *     @OA\Property(property="id", type="integer", readOnly=true, example=101),
 *     @OA\Property(property="enonce", type="string", example="Quelle est la capitale de la France ?"),
 *     @OA\Property(property="type", type="string", enum={"choix_unique", "choix_multiple", "texte_libre"}),
 *     @OA\Property(property="points", type="integer", example=2),
 *     @OA\Property(
 *         property="options",
 *         type="array",
 *         description="La liste des options de réponse (si QCM)",
 *         @OA\Items(ref="#/components/schemas/Option")
 *     )
 * )
 */
class QuestionSchema {}