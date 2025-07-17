<?php

namespace App\Http\Controllers\Api;




use App\Models\Parents;
use Illuminate\Support\Facades\Mail;
use App\Mail\EnvoiIdentifiants;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;


use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApprenantController extends Controller
{

    private function checkAdminOrSuperviseur()
    {
        $user = Auth::user();
        if (!in_array($user->role->libelle, ['superviseur', 'administrateur'])) {
            // On renvoie directement une réponse et on arrête l'exécution
            abort(403, 'Accès non autorisé. Seuls les administrateurs ou superviseurs sont permis.');
        }
    }
    private function checkAdminOrSuperviseurOrCaissier()
    {
        $user = Auth::user();
        if (!in_array($user->role->libelle, ['superviseur', 'administrateur', 'caissier'])) {
            // On renvoie directement une réponse et on arrête l'exécution
            abort(403, 'Accès non autorisé. Seuls les administrateurs ou superviseurs ou caissiers ou formateurssont permis.');
        }
    }
    // Lister tous les apprenants
    public function index(Request $request)
    {
        $this->checkAdminOrSuperviseurOrCaissier();


        $query = Apprenant::with('utilisateur');
        if ($request->filled('is_active')) {
            $query->whereHas('utilisateur', function ($q) use ($request) {
                $q->where('is_active', $request->is_active);
            });
        }

        if ($request->filled('nom')) {
            $query->whereHas('utilisateur', function ($q) use ($request) {
                $q->where('nom', 'like', '%' . $request->nom . '%');
            });
        }

        if ($request->filled('prenom')) {
            $query->whereHas('utilisateur', function ($q) use ($request) {
                $q->where('prenom', 'like', '%' . $request->prenom . '%');
            });
        }

        if ($request->filled('annee')) {
            $query->whereHas('utilisateur', function ($q) use ($request) {
                $q->whereYear('date_naissance', $request->annee);
            });
        }

        $apprenants = $query->paginate($request->get('per_page', 10)); // 10 par défaut
        return response()->json($apprenants);
    }
    // Créer un apprenant
    public function store(Request $request)
    {
        $this->checkAdminOrSuperviseur();
        $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'genre' => 'required|string',
            'email' => 'required|email|unique:utilisateurs,email',
            'date_naissance' => 'required|date',
            'niveau_etude' => 'required|string',
            'statut_actuel' => 'required|string',
            'parent_id' => 'nullable|exists:parents,id',
        ]);

        $login = strtolower(Str::slug($request->prenom)) . rand(100, 999);
        $passwordPlain = Str::random(10);
        $codeVerif = rand(100000, 999999);

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'login' => $login,
            'email' => $request->email,
            'genre' => $request->genre,
            'date_naissance' => $request->date_naissance,
            'password' => Hash::make($passwordPlain),
            'role_id' => 4, // Apprenant
            'verification_code' => $codeVerif,
            'email_verified' => false,
            'doit_changer_mot_de_passe' => true,
        ]);

        do {

            $matricule = 'AP' . now()->format('d') . strtoupper(substr($user->nom, 0, 2)) . Carbon::parse($user->date_naissance)->format('d') . strtoupper(substr($user->prenom, -2)) . Carbon::parse($user->date_naissance)->format('y') . rand(10, 99);
        } while (Apprenant::where('matriculeAP', $matricule)->exists());

        $apprenant = Apprenant::create([
            'utilisateur_id' => $user->id,
            'matriculeAP' => $matricule,
            'niveau_etude' => $request->niveau_etude,
            'statut_actuel' => $request->statut_actuel,
            'parent_id' => $request->parent_id,
        ]);

        Mail::to($user->email)->send(new EnvoiIdentifiants($user, $passwordPlain, $matricule));

        return response()->json([
            'message' => 'Apprenant créé avec succès.',
            'user' => $user,
            'apprenant' => $apprenant
        ]);
    }

    // Mettre à jour un apprenant
    public function update(Request $request, $id)
    {
        $apprenant = Apprenant::with('utilisateur')->findOrFail($id);
        $user = Auth::user();

        // Vérifie que seul admin ou utilisateur concerné peut modifier
        if ($user->role_id !== 1 && $user->id !== $apprenant->utilisateur_id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('utilisateurs')->ignore($apprenant->utilisateur_id),
            ],
            'login' => [
                'sometimes',
                'string',
                Rule::unique('utilisateurs', 'login')->ignore($apprenant->utilisateur_id),
            ],
            'date_naissance' => 'sometimes|date',
            'genre' => 'sometimes|string',
            'password' => 'sometimes|string|min:6',
            'photo_profil' => 'sometimes|file|mimes:jpg,jpeg,png|max:2048',
            'niveau_etude' => 'sometimes|string',
            'statut_actuel' => 'sometimes|string',
            'parent_id' => 'sometimes|exists:parents,id',
        ]);

        // Hash mot de passe si fourni
        if ($request->filled('password')) {
            $apprenant->utilisateur->password = bcrypt($request->password);
        }

        // Mise à jour photo si fournie
        if ($request->hasFile('photo_profil')) {
            if ($apprenant->utilisateur->photo_profil) {
                Storage::disk('public')->delete($apprenant->utilisateur->photo_profil);
            }
            $photoPath = $request->file('photo_profil')->store('photos', 'public');
            $apprenant->utilisateur->photo_profil = $photoPath;
        }

        // Mise à jour des données utilisateur
        $apprenant->utilisateur->fill($request->only([
            'nom', 'prenom', 'login', 'email', 'genre', 'date_naissance'
        ]));
        $apprenant->utilisateur->save();

        // Mise à jour des champs spécifiques apprenant
        $apprenant->fill($request->only([
            'niveau_etude',
            'statut_actuel',
            'parent_id'
        ]));

        // Mise à jour date dernière connexion
        $apprenant->derniere_connexion = now();

        $apprenant->save();

        return response()->json([
            'message' => 'Apprenant mis à jour avec succès.',
            'apprenant' => $apprenant->fresh(),
        ]);
    }


    // Afficher un apprenant specifique
    public function show($id)
    {
        $apprenant = Apprenant::with('utilisateur')->findOrFail($id);
        return response()->json($apprenant);
    }

    //exporter la liste des apprenants en format pdf
    public function exportPDFApprenants()
    {
        $this->checkAdminOrSuperviseur();
        $apprenants = Apprenant::with('utilisateur')->get();

        $html = '<h1>Liste des apprenants</h1>';
        $html .= '<table border="1" cellspacing="0" cellpadding="5">';
        $html .= '<thead><tr><th>Nom</th><th>Prénom</th><th>Email</th></tr></thead><tbody>';

        foreach ($apprenants as $apprenant) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($apprenant->utilisateur->nom ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($apprenant->utilisateur->prenom ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($apprenant->utilisateur->email ?? '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $pdf = Pdf::loadHTML($html);

        return $pdf->download('apprenants.pdf');
    }

}
