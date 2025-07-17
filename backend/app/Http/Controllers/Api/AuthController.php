<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Mail\EnvoiIdentifiants;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;



class AuthController extends Controller
{

    private function checkAdminOrSuperviseur()
    {
        $user = Auth::user();
        if (!in_array($user->role->libelle, ['superviseur', 'administrateur'])) {
            // On renvoie directement une réponse et on arrête l'exécution
            abort(403, 'Accès non autorisé. Seuls les administrateurs ou superviseurs sont permis.');
        }
    }
    public function register(Request $request)
    {
        $currentUser = $request->user(); // utilisateur connecté qui fait la requête


        if (!$currentUser) {
            return response()->json([
                'message' => 'Utilisateur non authentifié.'
            ], 401);
        }

        $roleToCreate = $request->role_id;

        // Vérification des droits de création
        if ($currentUser->role_id == 1) {
            // Admin peut créer tout sauf on interdit pas explicitement ici
            // On autorise donc la création de superviseur (2) ici
            } elseif ($currentUser->role_id == 2) {
            // Superviseur ne peut pas créer admin (1) ni superviseur (2)
            if (in_array($roleToCreate, [1, 2])) {
                return response()->json([
                    'message' => "Vous n'êtes pas autorisé à créer ce type d'utilisateur."
                ], 403);
            }
            } else {
            // Tous les autres n'ont pas le droit de créer des utilisateurs
            return response()->json([
                'message' => "Vous n'avez pas la permission de créer des utilisateurs."
            ], 403);
        }
        // Règles de validation de base
        $rules = [
            'nom' => 'required|string',
            'prenom' => 'required|string',
           'email' => [
                'required',
                'email',
                'unique:utilisateurs,email',
                'regex:/^([a-zA-Z0-9._%+-]+)@(gmail\.com|institutsaintjean\.org)$/i',
            ],
            'genre' => 'required|string',
            'date_naissance' => 'required|date',
            'role_id' => 'required|integer',
        ];

        $messages = [
            'email.regex' => "L'email doit être une adresse gmail.com ou institutsaintjean.org valide.",
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        // Validation de base échoue ?
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Calculer l'âge
        $dateNaissance = Carbon::parse($request->date_naissance);
        $age = $dateNaissance->age;

        // Règles d'âge selon rôle
        if ($request->role_id == 4 && $age < 6) {
            return response()->json([
                'message' => "Un apprenant doit avoir au moins 6 ans.",
            ], 422);
        } elseif ($request->role_id != 4 && $age < 18) {
            return response()->json([
                'message' => "Les autres rôles doivent avoir au moins 18 ans.",
            ], 422);
        }

        // Toutes validations passées, création de l'utilisateur
        $login = strtolower(Str::slug($request->prenom)) . rand(100, 999);
        // Assurez-vous que ce login est unique
        while (User::where('login', $login)->exists()) {
            $login = strtolower(Str::slug($request->prenom)) . rand(100, 999);
        }
        // Assurez-vous que ce login est unique
        while (User::where('login', $login)->exists()) {
            $login = strtolower(Str::slug($request->prenom)) . rand(100, 999);
        }
        $passwordPlain = Str::random(10);
        $verificationCode = rand(100000, 999999);

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'genre' => $request->genre,
            'date_naissance' => $request->date_naissance,
            'email' => $request->email,
            'password' => bcrypt($passwordPlain),
            'role_id' => $request->role_id,
            'login' => $login,
            'email_verified' => false,
            'doit_changer_mot_de_passe' => true,
            'verification_code' => $verificationCode,
        ]);

        $user->refresh(); // Recharge les relations

        switch ($request->role_id) {
            case 1: // administrateur
                $matricule = optional($user->administrateur)->matriculeAD;
                break;
            case 2: // superviseur
                $matricule = optional($user->superviseur)->matriculeSU;
                break;
            case 3: // formateur
                $matricule = optional($user->formateur)->matriculeAD;
                break;
            case 4: // apprenant
                $matricule = optional($user->apprenant)->matriculeAP;
                break;
            case 5: // parent
                $matricule = optional($user->parents)->matriculePA;
                break;
            case 6: // caissier
                $matricule = optional($user->caissier)->matriculeCA;
                break;
            case 7: // auditeur
                $matricule = optional($user->auditeur)->matriculeAU;
                break;
            case 8: // vendeur
                $matricule = optional($user->vendeur)->matriculeVE;
                break;
            default:
                $matricule = 'N/A';
        }

        // Envoi de l’email
        Mail::to($user->email)->send(new EnvoiIdentifiants($user, $passwordPlain, $matricule));

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Utilisateur inscrit avec succès. Vérifiez votre email pour vos identifiants.',
            'user' => $user,
            'token' => $token
        ], 201);
    }


    // Connexion avec contrôle email vérifié + changement mot de passe forcé
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('login', $request->login)
                    //->orWhere('matricule', $request->login)
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Login ou matricule incorrect.'], 401);
        }

        // Vérifie si le compte est désactivé IMMÉDIATEMENT
        if ($user->is_active == 0) {
            return response()->json(['message' => 'Votre compte est désactivé. Veuillez contacter un administrateur.'], 403);
        }

        // Vérifie si le compte est verrouillé
        if ($user->verrouille_jusqua && now()->lessThan($user->verrouille_jusqua)) {
            return response()->json([
                'message' => 'Votre compte est temporairement verrouillé. Réessayez après ' . $user->verrouille_jusqua->diffForHumans()
            ], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            $user->tentatives_echouees += 1;

            if ($user->tentatives_echouees >= 5) {
                $user->verrouille_jusqua = now()->addMinutes(15);
                $user->tentatives_echouees = 0;
            }

            $user->save();
            return response()->json(['message' => 'Mot de passe incorrect.'], 401);
        }

        if (!$user->email_verified) {
            return response()->json(['message' => 'Veuillez vérifier votre adresse email.'], 403);
        }

        // Reset sécurité
        $user->tentatives_echouees = 0;
        $user->verrouille_jusqua = null;
        $user->save();

        // Génération du token
        $token = $user->createToken('auth_token')->plainTextToken;

        if ($user->doit_changer_mot_de_passe) {
            return response()->json([
                'message' => 'Mot de passe temporaire. Vous devez le modifier.',
                'changer_password' => true,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        }

        return response()->json([
            'message' => 'Connexion réussie.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // Modifier login et mot de passe (après première connexion)
    public function modifierIdentifiants(Request $request)
    {
        $request->validate([
            'login' => 'required|string|unique:utilisateurs,login,' . Auth::id(),
            'password' => 'required|string|confirmed|min:6',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->login = $request->login;
        $user->password = Hash::make($request->password);
        $user->doit_changer_mot_de_passe = false;
        $user->save();

        return response()->json(['message' => 'Identifiants mis à jour avec succès.']);
    }




    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès']);
    }

   public function forgotPassword(Request $request)
{
    try {
        $data = $request->all();
        Log::info('Request data:', ['data' => $data]);

        $request->validate([
            'email' => 'required|email|exists:utilisateurs,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Pas besoin de ce test, la validation `exists` l'a déjà fait :
        // if (!$user) ...

        $code = rand(100000, 999999);
        $user->verification_code = $code;
        $user->save();

        Mail::raw("Votre code de réinitialisation est : $code", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Réinitialisation du mot de passe');
        });

        return response()->json([
            'message' => 'Un code de réinitialisation a été envoyé à votre adresse email.'
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation échouée.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erreur forgotPassword: ' . $e->getMessage());
        return response()->json(['message' => 'Erreur serveur interne.'], 500);
    }
}



    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:utilisateurs,email',
            'verification_code' => 'required',
            'new_password' => 'required|confirmed|min:6',
        ]);

        $user = User::where('email', $request->email)
                    ->where('verification_code', $request->verification_code)
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Code de vérification invalide.'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->verification_code = null; // On le vide
        $user->doit_changer_mot_de_passe = false; // Facultatif
        $user->save();

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}
