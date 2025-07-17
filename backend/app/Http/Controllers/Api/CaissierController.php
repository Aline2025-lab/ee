<?php

namespace App\Http\Controllers\Api;

use App\Models\Caissier;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Mail\EnvoiIdentifiants;
use App\Models\User;
use Illuminate\Validation\Rule;

class CaissierController extends Controller
{
    private function checkAdminOrSuperviseur()
    {
        $user = Auth::user();
        if (!in_array($user->role->libelle, ['superviseur', 'administrateur'])) {
            // On renvoie directement une réponse et on arrête l'exécution
            abort(403, 'Accès non autorisé. Seuls les administrateurs ou superviseurs sont permis.');
        }
    }
    //lister tous les caissier
    public function index(Request $request)
    {
        $this->checkAdminOrSuperviseur();

        $query = Caissier::with('utilisateur');
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

        $caissiers = $query->paginate($request->get('per_page', 10)); // 10 par défaut
        return response()->json($caissiers);
    }



    //ajouter un caissier
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
            'role_id' => 6,
            'verification_code' => $codeVerif,
            'email_verified' => false,
            'doit_changer_mot_de_passe' => true,
        ]);

        do {
            $matricule = 'CA' . now()->format('d') . strtoupper(substr($user->nom, 0, 2)) . Carbon::parse($user->date_naissance)->format('d') . strtoupper(substr($user->prenom, -2)) . Carbon::parse($user->date_naissance)->format('y') . rand(10, 99);
        } while (Caissier::where('matriculeCA', $matricule)->exists());

        $caissier = Caissier::create([
            'utilisateur_id' => $user->id,
            'matriculeCA' => $matricule,
            'date_derniere_action' => now(),
        ]);

        Mail::to($user->email)->send(new EnvoiIdentifiants($user, $passwordPlain, $matricule));

        return response()->json([
            'message' => 'Caissier créé avec succès.',
            'user' => $user,
            'caissier' => $caissier
        ]);
    }

    //afficher un caissier specifique
    public function show( $id)
    {
        $caissier= Caissier::with('utilisateur')->findOrFail($id);

        return response()->json($caissier);
    }


    //mettre a jour un caissier
    public function update(Request $request, $id)
    {
        $caissier = Caissier::with('utilisateur')->findOrFail($id);
        $user = Auth::user();

        // Vérifie que seul admin ou l'utilisateur concerné peut modifier
        if ($user->role_id !== 1 && $user->id !== $caissier->utilisateur_id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('utilisateurs', 'email')->ignore($caissier->utilisateur_id),
            ],
            'login' => [
                'sometimes',
                'string',
                Rule::unique('utilisateurs', 'login')->ignore($caissier->utilisateur_id),
            ],
            'date_naissance' => 'sometimes|date',
            'genre' => 'sometimes|string',
            'password' => 'sometimes|string|min:6',
            'photo_profil' => 'sometimes|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Hash du mot de passe si présent
        if ($request->filled('password')) {
            $caissier->utilisateur->password = bcrypt($request->password);
        }

        // Mise à jour photo
        if ($request->hasFile('photo_profil')) {
            if ($caissier->utilisateur->photo_profil) {
                Storage::disk('public')->delete($caissier->utilisateur->photo_profil);
            }
            $photoPath = $request->file('photo_profil')->store('photos', 'public');
            $caissier->utilisateur->photo_profil = $photoPath;
        }

        // Mise à jour des autres champs utilisateur
        $caissier->utilisateur->fill($request->only([
            'nom', 'prenom', 'login', 'email', 'genre', 'date_naissance'
        ]));
        $caissier->utilisateur->save();

        // Mise à jour date dernière action
        $caissier->date_derniere_action = now();
        $caissier->save();

        return response()->json([
            'message' => 'Caissier mis à jour avec succès.',
            'caissier' => $caissier->fresh()
        ]);
    }


    //exporter la liste des caissiers au format pdf
    public function exportPDFCaissiers()
    {
        $this->checkAdminOrSuperviseur();
        $caissiers = Caissier::with('utilisateur')->get();

        $html = '<h1>Liste des caissiers</h1>';
        $html .= '<table border="1" cellspacing="0" cellpadding="5">';
        $html .= '<thead><tr><th>Nom</th><th>Prénom</th><th>Email</th></tr></thead><tbody>';

        foreach ($caissiers as $caissier) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($caissier->utilisateur->nom ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($caissier->utilisateur->prenom ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($caissier->utilisateur->email ?? '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $pdf = Pdf::loadHTML($html);

        return $pdf->download('caissiers.pdf');
    }

}
