<?php



namespace App\Http\Controllers;

use OpenApi\Annotations as OA;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;


use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Nom de votre API",
 *     description="Description complète de votre API",
 *     @OA\Contact(
 *         email="Aline@gmail.com",
 *         name="BE I.T AFRICA"
 *     ),
 *     @OA\License(
 *         name="Licence MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
  * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Serveur principal de l'API"
 * )
 */
/**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Liste des rôles",
     *     tags={"Rôles"},
     *     @OA\Response(
     *         response=200,
     *         description="Succès"
     *     )
     * )
*/

// abstract class Controller
// {
//     use AuthorizesRequests, ValidatesRequests;
// }

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Vérifie si l'utilisateur connecté a l'un des rôles spécifiés.
     * Si ce n'est pas le cas, lève une exception d'autorisation 403.
     *
     * @param array $roles Les libellés des rôles autorisés (ex: ['superviseur', 'formateur'])
     * @return void
     */
    protected function authorizeRoles(array $roles): void
    {
        // Assurez-vous que l'utilisateur est connecté
        if (!Auth::check()) {
            abort(401, 'Non authentifié.'); // 401 Unauthorized
        }
        /** @var \App\Models\User $user */

        $user = Auth::user();
        // Pré-chargez la relation 'role' pour éviter des requêtes supplémentaires
        $user->loadMissing('role');

        if (!$user->role || !in_array($user->role->libelle, $roles)) {
            abort(403, 'Accès non autorisé.'); // 403 Forbidden
        }
    }
}
