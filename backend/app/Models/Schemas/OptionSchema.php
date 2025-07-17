<?php

namespace App\Models\Schemas;

/**
 * @OA\Schema(
 *     schema="Option",
 *     title="Option",
 *     description="Modèle de données pour une option de réponse",
 *     @OA\Property(property="id", type="integer", readOnly=true, example=501),
 *     @OA\Property(property="texte_option", type="string", example="Paris"),
 *     @OA\Property(property="est_correcte", type="boolean", example=true)
 * )
 */
class OptionSchema {}