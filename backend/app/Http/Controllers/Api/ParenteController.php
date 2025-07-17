<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parents;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;



use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Mail\EnvoiIdentifiants;
use App\Models\User;
use App\Models\Formation;

use App\Models\Apprenant;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ParenteController extends Controller
{

    private function checkAdminOrSuperviseur()
    {
        $user = Auth::user();
        if (!in_array($user->role->libelle, ['superviseur', 'administrateur'])) {
            // On renvoie directement une réponse et on arrête l'exécution
            abort(403, 'Accès non autorisé. Seuls les administrateurs ou superviseurs sont permis.');
        }
    }
    //lister tous les parents
    public function index(Request $request)
    {
        $this->checkAdminOrSuperviseur();

        $query = Parents::with('utilisateur');
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

        $parents = $query->paginate($request->get('per_page', 10)); // 10 par défaut
        return response()->json($parents);
    }



    //Ajouter un nouveau parent
    public function store(Request $request)
    {
        $this->checkAdminOrSuperviseur();
        $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'genre' => 'required|string',
            'email' => 'required|email|unique:utilisateurs,email',
            'date_naissance' => 'required|date',
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
            'role_id' => 4,
            'verification_code' => $codeVerif,
            'email_verified' => false,
            'doit_changer_mot_de_passe' => true,
        ]);

        do {
            $matricule = 'PA' . now()->format('d') . strtoupper(substr($user->nom, 0, 2)) . Carbon::parse($user->date_naissance)->format('d') . strtoupper(substr($user->prenom, -2)) . Carbon::parse($user->date_naissance)->format('y') . rand(10, 99);
        } while (Parents::where('matriculePA', $matricule)->exists());

        $parent = Parents::create([
            'utilisateur_id' => $user->id,
            'matriculePA' => $matricule,
            'date_derniere_action' => now(),
        ]);

        Mail::to($user->email)->send(new EnvoiIdentifiants($user, $passwordPlain, $matricule));

        return response()->json([
            'message' => 'Parent créé avec succès.',
            'user' => $user,
            'parent' => $parent
        ]);
    }

    //afficher un parent specifique
    public function show( $id)
    {
        $parent = Parents::with('utilisateur')->findOrFail($id);

        return response()->json($parent);
    }




    //mettre a jour un parent
    public function update(Request $request, $id)
    {
        $parent = Parents::with('utilisateur')->findOrFail($id);
        $user = Auth::user();

        // Vérification : seul l'admin ou le parent concerné peut modifier
        if ($user->role_id !== 1 && $user->id !== $parent->utilisateur_id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('utilisateurs', 'email')->ignore($parent->utilisateur_id),
            ],
            'login' => [
                'sometimes',
                'string',
                Rule::unique('utilisateurs', 'login')->ignore($parent->utilisateur_id),
            ],
            'date_naissance' => 'sometimes|date',
            'genre' => 'sometimes|string',
            'password' => 'sometimes|string|min:6',
            'photo_profil' => 'sometimes|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Hash du mot de passe s’il est fourni
        if ($request->filled('password')) {
            $parent->utilisateur->password = bcrypt($request->password);
        }

        // Mise à jour de la photo
        if ($request->hasFile('photo_profil')) {
            // Suppression ancienne photo si existe
            if ($parent->utilisateur->photo_profil) {
                Storage::disk('public')->delete($parent->utilisateur->photo_profil);
            }
            $photoPath = $request->file('photo_profil')->store('photos', 'public');
            $parent->utilisateur->photo_profil = $photoPath;
        }

        // Mise à jour des autres champs utilisateur
        $parent->utilisateur->fill($request->only([
            'nom', 'prenom', 'login', 'email', 'genre', 'date_naissance'
        ]));
        $parent->utilisateur->save();

        // Mise à jour de la date dernière action dans la table parents
        $parent->date_derniere_action = now();
        $parent->save();

        return response()->json([
            'message' => 'Parent mis à jour avec succès.',
            'parent' => $parent->fresh()
        ]);
    }

    //exporter la liste des parent aux format pdf
    public function exportPDFParents()
    {
        $this->checkAdminOrSuperviseur();
        $parents = Parents::with('utilisateur')->get();

        $html = '<h1>Liste des parents</h1>';
        $html .= '<table border="1" cellspacing="0" cellpadding="5">';
        $html .= '<thead><tr><th>Nom</th><th>Prénom</th><th>Email</th></tr></thead><tbody>';

        foreach ($parents as $parent) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($parent->utilisateur->nom ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($parent->utilisateur->prenom ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($parent->utilisateur->email ?? '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $pdf = Pdf::loadHTML($html);

        return $pdf->download('parents.pdf');
    }

     //Récupère la liste des enfants liés au parent authentifié.
    public function mesEnfants()
    {
        // Récupérer l'utilisateur parent authentifié
        $parent = Auth::user();

        // Vérification de sécurité
        if ($parent->role->libelle !== 'Parents') {
            return response()->json([
                'message' => 'Accès non autorisé.'
            ], 403);
        }
        //$enfants = $parent->enfants;

        $enfants = $parent->enfants->select('matricule', 'nom', 'prenom', 'email')->get();

        // 4. Retourner la réponse
        return response()->json([
            'message' => 'Liste des apprenants récupérée avec succès.',
            'data' => $enfants
        ]);
    }

    public function formationsDeMesEnfantsParEnfant()
    {
        /** @var \App\Models\User $parent */ 
        $parent = Auth::user();

        if ($parent->role->libelle !== 'Parent') {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        // On charge les enfants, et POUR chaque enfant, on pré-charge ses formations
        $enfantsAvecFormations = $parent->enfants()->with('formations')->get();

        // La structure de la réponse sera un tableau d'enfants,
        // où chaque objet enfant contiendra un tableau de ses formations.
        return response()->json([
            'message' => 'Liste des formations par apprenant récupérée avec succès.',
            'data' => $enfantsAvecFormations
        ]);
    }


}
