<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Administrateur;
use Illuminate\Http\Request;



use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Mail\EnvoiIdentifiants;
use App\Models\User;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;



class AdministrateurController extends Controller
{
    //lister tous les admins
    public function index(Request $request)
    {


        $query = Administrateur::with('utilisateur');
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

        $administrateurs = $query->paginate($request->get('per_page', 10)); // 10 par défaut
        return response()->json($administrateurs);
    }


    //ajouter un admin
    public function store(Request $request)
    {
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
            'role_id' => 1,
            'verification_code' => $codeVerif,
            'email_verified' => false,
            'doit_changer_mot_de_passe' => true,
        ]);

        do {
            $matricule = 'AD' . now()->format('d') . strtoupper(substr($user->nom, 0, 2)) . Carbon::parse($user->date_naissance)->format('d') . strtoupper(substr($user->prenom, -2)) . Carbon::parse($user->date_naissance)->format('y') . rand(10, 99);
        } while (Administrateur::where('matriculeAD', $matricule)->exists());

        $admin = Administrateur::create([
            'utilisateur_id' => $user->id,
            'matriculeAD' => $matricule,
            'date_derniere_action' => now(),
        ]);

        Mail::to($user->email)->send(new EnvoiIdentifiants($user, $passwordPlain, $matricule));

        return response()->json([
            'message' => 'Administrateur créé avec succès.',
            'user' => $user,
            'administrateur' => $admin
        ]);
    }

    //afficher un admin specifique
    public function show( $id)
    {
        $administrateur = Administrateur::with('utilisateur')->findOrFail($id);

        return response()->json($administrateur);
    }

    public function edit(administrateur $administrateur)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $admin = Administrateur::with('utilisateur')->findOrFail($id);
        $user = Auth::user();

        // Vérifie si c’est un admin ou le propriétaire
        if ($user->role_id !== 1 && $user->id !== $admin->utilisateur_id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Validation
        $validated = $request->validate([
            'nom'           => 'sometimes|string',
            'prenom'        => 'sometimes|string',
            'email'         => ['sometimes','email', Rule::unique('utilisateurs')->ignore($admin->utilisateur_id)],
            'login'         => ['sometimes','string',Rule::unique('utilisateurs','login')->ignore($admin->utilisateur_id)],
            'date_naissance'=> 'sometimes|date',
            'genre'         => 'sometimes|string',
            'password'      => 'sometimes|string|min:6',
            'photo_profil'  => 'sometimes|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Mot de passe
        if ($request->filled('password')) {
            $admin->utilisateur->password = bcrypt($request->password);
        }

        // Photo de profil
        if ($request->hasFile('photo_profil')) {
            // Supprime l’ancienne si elle existe
            if ($admin->utilisateur->photo_profil) {
                Storage::disk('public')->delete($admin->utilisateur->photo_profil);
            }
            // Stocke et assigne
            $path = $request->file('photo_profil')->store('photos', 'public');
            $admin->utilisateur->photo_profil = $path;
        }

        // Autres champs (hors photo)
        $admin->utilisateur->fill($request->only([
            'nom','prenom','login','email','genre','date_naissance'
        ]));

        // Sauvegarde utilisateur
        $admin->utilisateur->save();

        // Mise à jour date dernière action
        $admin->date_derniere_action = now();
        $admin->save();

        // Réponse avec URL si dispos
        return response()->json([
            'message' => 'Administrateur mis à jour avec succès.',
            'administrateur' => $admin->fresh('utilisateur'),
            'photo_url' => $admin->utilisateur->photo_profil
                ? asset('storage/' . $admin->utilisateur->photo_profil)
                : null,
        ]);
    }



    //exporter la liste des admins en format pdf
    public function exportPDFAdministrateurs()
    {
        // Récupère les administrateurs avec leurs utilisateurs liés
        $administrateurs = Administrateur::with('utilisateur')->get();
        logger()->info('Administrateurs:', $administrateurs->toArray());

        // Construis le HTML à la main
        $html = '<h1>Liste des administrateurs</h1>';
        $html .= '<table border="1" cellspacing="0" cellpadding="5">';
        $html .= '<thead><tr><th>Nom</th><th>Prénom</th><th>Email</th></tr></thead><tbody>';

        foreach ($administrateurs as $admin) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($admin->utilisateur->nom ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($admin->utilisateur->prenom ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($admin->utilisateur->email ?? '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Génère le PDF à partir du HTML
        $pdf = Pdf::loadHTML($html);



        // Sauvegarder dans storage/app/public/pdfs/
        Storage::put('public/pdfs/administrateurs.pdf', $pdf->output());

        // Retourne le PDF en téléchargement
            return $pdf->download('administrateurs.pdf');
    }

}
