<?php

namespace App\Http\Controllers\Api;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\SuperviseurExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Superviseur;



use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Mail\EnvoiIdentifiants;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use Illuminate\Validation\Rule;

class SuperviseurController extends Controller
{
     private function checkAdministrateurOnly()
    {
        $user = Auth::user();
        if ($user->role->libelle !== 'administrateur') {
            abort(403, 'Accès non autorisé. Seuls les administrateurs sont permis.');
        }
    }

    private function checkAdminOrSuperviseur()
    {
        $user = Auth::user();
        if (!in_array($user->role->libelle, ['superviseur', 'administrateur'])) {
            // On renvoie directement une réponse et on arrête l'exécution
            abort(403, 'Accès non autorisé. Seuls les administrateurs ou superviseurs sont permis.');
        }
    }

    //lister tous les superviseurs
    public function index(Request $request)
    {
        $this->checkAdministrateurOnly();

        $query = Superviseur::with('utilisateur');
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

        $superviseurs = $query->paginate($request->get('per_page', 10)); // 10 par défaut

        return response()->json($superviseurs);

    }


    public function create()
    {
        //
    }

    //ajouter un superviseur
    public function store(Request $request)
    {
        $this->checkAdministrateurOnly();
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
            'role_id' => 5,
            'verification_code' => $codeVerif,
            'email_verified' => false,
            'doit_changer_mot_de_passe' => true,
        ]);

        do {
            $matricule = 'SU' . now()->format('d') . strtoupper(substr($user->nom, 0, 2)) . Carbon::parse($user->date_naissance)->format('d') . strtoupper(substr($user->prenom, -2)) . Carbon::parse($user->date_naissance)->format('y') . rand(10, 99);
        } while (Superviseur::where('matriculeSU', $matricule)->exists());

        $superviseur = Superviseur::create([
            'utilisateur_id' => $user->id,
            'matriculeSU' => $matricule,
            'date_derniere_action' => now(),
        ]);

        Mail::to($user->email)->send(new EnvoiIdentifiants($user, $passwordPlain, $matricule));

        return response()->json([
            'message' => 'Superviseur créé avec succès.',
            'user' => $user,
            'superviseur' => $superviseur
        ]);
    }

    //afficher un superviseur specifique
    public function show( $id)
    {
        $this->checkAdministrateurOnly();
        $superviseur = Superviseur::with('utilisateur')->findOrFail($id);

        return response()->json($superviseur);
    }



    //mettre a jour un superviseur
    public function update(Request $request, $id)
    {
        $superviseur = Superviseur::with('utilisateur')->findOrFail($id);
        $user = Auth::user();

        // Autorisation : seul l'utilisateur concerné ou un admin peut modifier
        if ($user->id !== $superviseur->utilisateur_id && $user->role_id !== 1) {
        return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Validation
        $validated = $request->validate([
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'login' => [
                'sometimes',
                'string',
                Rule::unique('utilisateurs', 'login')->ignore($superviseur->utilisateur_id)
            ],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('utilisateurs', 'email')->ignore($superviseur->utilisateur_id)
            ],
            'date_naissance' => 'sometimes|date',
            'genre' => 'sometimes|string',
            'password' => 'sometimes|string|min:6',
            'role_id' => 'prohibited',
            'photo_profil' => 'sometimes|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Mise à jour des données utilisateur
        $utilisateur = $superviseur->utilisateur;
        $utilisateur->fill($request->only([
            'nom', 'prenom', 'login', 'email', 'date_naissance', 'genre'
        ]));

        if ($request->filled('password')) {
            $utilisateur->password = Hash::make($request->password);
            $utilisateur->doit_changer_mot_de_passe = true;
        }

        // Mise à jour photo
        if ($request->hasFile('photo_profil')) {
            if ($superviseur->utilisateur->photo_profil) {
                Storage::disk('public')->delete($superviseur->utilisateur->photo_profil);
            }
            $photoPath = $request->file('photo_profil')->store('photos', 'public');
            $superviseur->utilisateur->photo_profil = $photoPath;
            $superviseur->utilisateur->save();
        }

        $utilisateur->save();

        // Mettre à jour la date de dernière action
        $superviseur->date_derniere_action = now();
        $superviseur->save();

        return response()->json([
            'message' => 'Informations du superviseur mises à jour avec succès.',
            'superviseur' => $superviseur->fresh('utilisateur'),
        ]);
    }

    //exporter la liste des superviseurs en format excel
    public function exportExcel()
    {
        $this->checkAdministrateurOnly();
        return Excel::download(new SuperviseurExport, 'superviseurs.xlsx');
    }

    //exporter la liste des superviseurs en format pdf
    public function exportPDF()
    {
        $this->checkAdministrateurOnly();
        //$superviseurs = Superviseur::with('utilisateurs')->get();

        $superviseurs = Superviseur::all();
        logger()->info('Superviseurs:', $superviseurs->toArray());

        // Construis le HTML à la main
        $html = '<h1>Liste des superviseurs</h1>';
        $html .= '<table border="1" cellspacing="0" cellpadding="5">';
        $html .= '<thead><tr><th>Nom</th><th>Prénom</th><th>Email</th></tr></thead><tbody>';

        foreach ($superviseurs as $superviseur) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($superviseur->nom) . '</td>';
            $html .= '<td>' . htmlspecialchars($superviseur->prenom) . '</td>';
            $html .= '<td>' . htmlspecialchars($superviseur->email) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Génère le PDF à partir du HTML
        $pdf = Pdf::loadHTML($html);

        // Retourne le PDF en téléchargement
        return $pdf->download('superviseurs.pdf');
    }
}
