<?php

namespace App\Http\Controllers\Api;


use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\Formateur;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\EnvoiIdentifiants;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FormateurController extends Controller
{
    private function checkAdminOrSuperviseur()
    {
        $user = Auth::user();
        if (!in_array($user->role->libelle, ['superviseur', 'administrateur'])) {
            // On renvoie directement une réponse et on arrête l'exécution
            abort(403, 'Accès non autorisé. Seuls les administrateurs ou superviseurs sont permis.');
        }
    }


    // Lister tous les formateurs
    public function index(Request $request)
    {
        $this->checkAdminOrSuperviseur();

        $query = Formateur::with('utilisateur');
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

        $formateurs = $query->paginate($request->get('per_page', 10)); // 10 par défaut
        return response()->json($formateurs);
    }

    // Créer un nouveau formateur
    public function store(Request $request)
    {
        $this->checkAdminOrSuperviseur();
        //return response()->json(['debug' => 'ok']);
        Log::info('Requête brute :', [
            'nom' => $request->input('nom'),
            'cv' => $request->file('cv'),
        ]);
            Log::info('Request Data:', $request->all());
        $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'genre' => 'required|string',
            'email' => 'required|email|unique:utilisateurs,email',
            'date_naissance' => 'required|date',
            'specialite' => 'required|string',
            'cv' => 'nullable|file|mimes:pdf,doc,docx,jpeg,jpg,png|max:2048', // max 2Mo
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
            'role_id' => 3, // formateur
            'verification_code' => $codeVerif,
            'email_verified' => false,
            'doit_changer_mot_de_passe' => true,
        ]);
        $cvPath = null;

        do{
                $matricule = 'FO' .
                now()->format('d') .
                strtoupper(substr($user->nom, 0, 2)) .
                Carbon::parse($user->date_naissance)->format('d') .
                strtoupper(substr($user->prenom, -2)) .
                Carbon::parse($user->date_naissance)->format('y') .
                rand(10, 99);  // ajoute un suffixe aléatoire pour plus de chance d'unicité
        } while (Formateur::where('matriculeAD', $matricule)->exists());

        $formateur = Formateur::create([
            'utilisateur_id' => $user->id,
            'matriculeAD' => $matricule,
            'specialite' => $request->specialite,
            'cv' => $cvPath,
        ]);


        Mail::to($user->email)->send(new EnvoiIdentifiants($user, $passwordPlain, $matricule));

        return response()->json([
            'message' => 'Formateur créé avec succès.',
            'user' => $user,
            'formateur' => $formateur
        ]);
    }

    // Mettre à jour un formateur
    public function update(Request $request, $id)
    {
        $formateur = Formateur::with('utilisateur')->findOrFail($id);
        $user = Auth::user();

        // Vérifie que l'utilisateur connecté est bien le propriétaire
        if ($user->id !== $formateur->utilisateur_id && $user->role->libelle !== 'administrateur') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Validation
        $validated = $request->validate([
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'date_naissance' => 'sometimes|date',
            'password' => 'sometimes|string|min:6',
            'genre' => 'sometimes|string',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('utilisateurs', 'email')->ignore($formateur->utilisateur_id)
            ],
            'login' => [
                'sometimes',
                'string',
                Rule::unique('utilisateurs', 'login')->ignore($formateur->utilisateur_id),
            ],
            'specialite' => 'sometimes|string',
            'cv' => 'sometimes|file|mimes:pdf,doc,docx,jpeg,jpg,png|max:2048',
            'role_id' => 'prohibited',
            'photo_profil' => 'sometimes|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Mise à jour du formateur
        if (isset($validated['specialite'])) {
            $formateur->update(['specialite' => $validated['specialite']]);
        }

        if ($request->hasFile('cv')) {
            // Supprimer l'ancien fichier s'il existe
            if ($formateur->cv && Storage::disk('public')->exists($formateur->cv)) {
                Storage::disk('public')->delete($formateur->cv);
            }

            // Stocker le nouveau fichier
            $cvPath = $request->file('cv')->store('cvs', 'public');

            // Mettre à jour le champ 'cv' dans la base de données
            $formateur->update(['cv' => $cvPath]);
        }

        // Mise à jour du mot de passe
        if (isset($validated['password'])) {
            $formateur->utilisateur->password = bcrypt($validated['password']);
        }

        // Mise à jour photo
        if ($request->hasFile('photo_profil')) {
            if ($formateur->utilisateur->photo_profil) {
                Storage::disk('public')->delete($formateur->utilisateur->photo_profil);
            }
            $photoPath = $request->file('photo_profil')->store('photos', 'public');
            $formateur->utilisateur->photo_profil = $photoPath;
            $formateur->utilisateur->save();
        }

        // Mettre à jour la date de dernière action
        $formateur->date_derniere_action = now();
        $formateur->save();

        // Mise à jour des infos utilisateur sauf rôle
        $formateur->utilisateur->fill(collect($validated)->only([
            'nom',
            'prenom',
            'login',
            'date_naissance',
            'genre',
            'email',
        ])->toArray());

        // Si l'utilisateur connecté est admin, autoriser la mise à jour du rôle
        if ($request->has('role_id') && $user->role->libelle === 'administrateur') {
            $formateur->utilisateur->role_id = $request->role_id;
        }

        $formateur->utilisateur->save();

        return response()->json([
            'message' => 'Informations mises à jour avec succès.',
            'formateur' => $formateur->fresh(),
            'utilisateur' => $formateur->utilisateur->fresh()
        ]);


    }



    // Afficher un formateur spécifique
    public function show($id)
    {
        $formateur = Formateur::with('utilisateur')->findOrFail($id);
        return response()->json($formateur);
    }
}
