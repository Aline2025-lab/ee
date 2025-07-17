<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MoMoService;
use App\Services\MoMoTransaction;
use App\Services\Payer;

use App\Models\Paiement;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use App\Models\Inscription;
use Illuminate\Support\Facades\DB;
use PDF;

class PaiementController extends Controller
{

    public function initier(Request $request)
    {
        if (Auth::user()->role->libelle !== 'caissier') {
            abort(403, 'Seuls les caissiers peuvent initier des paiements.');
        }

        $validated = $request->validate([
            'apprenant_id' => 'required|exists:apprenants,id',
            'formation_id' => 'required|exists:formations,id',
            'montant' => 'required|numeric|min:1',
            'methode' => 'required|in:OM,MOMO,carte_bancaire,espèce',
            'type_paiement' => 'required|in:frais_formation,frais_inscription,frais_certification,autres',
            'motif' => 'nullable|string',
        ]);

        $paiement = Paiement::create([
            'apprenant_id' => $validated['apprenant_id'],
            'formation_id' => $validated['formation_id'],
            'montant' => $validated['montant'],
            'methode' => $validated['methode'],
            'type_paiement' => $validated['type_paiement'],
            'motif' => $validated['motif'] ?? 'Paiement ' . $validated['type_paiement'],
            'statut' => 'en_attente', // <-- Statut initial
            'caissier_id' => Auth::id(),
            'date_paiement' => now(),
        ]);

        // TODO: Génération du reçu PDF ici

        return response()->json([
            'message' => 'Paiement initié. En attente de confirmation par l\'auditeur.',
            'paiement' => $paiement
        ], 201);
    }



        public function confirmer(Paiement $paiement)
        {
            if (Auth::user()->role->libelle !== 'auditeur') {
                abort(403, 'Seuls les auditeurs peuvent confirmer les paiements.');
            }

            if ($paiement->statut !== 'en_attente') {
                return response()->json(['message' => 'Ce paiement n\'est pas en attente de confirmation.'], 409);
            }

            try {
                DB::beginTransaction();

                // 1. Confirmer le paiement
                $paiement->update([
                    'statut' => 'effectue', // Votre statut pour un paiement réussi
                    'auditeur_id' => Auth::id(),
                    'date_confirmation' => now(),
                ]);

                // 2. Mettre à jour le statut de l'inscription correspondante
                $inscription = Inscription::where('apprenant_id', $paiement->apprenant_id)
                                        ->where('formation_id', $paiement->formation_id)
                                        ->first();

                if ($inscription) {
                    // Logique pour déterminer le statut (partiel ou payé)
                    // Pour l'instant, on met 'paye', à affiner si besoin
                    $inscription->update(['statut_paiement' => 'paye']);
                }

                DB::commit();

                return response()->json([
                    'message' => 'Paiement confirmé et inscription mise à jour.',
                    'paiement' => $paiement->fresh()
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Erreur lors de la confirmation.', 'error' => $e->getMessage()], 500);
            }
        }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Paiement $paiement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Paiement $paiement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Paiement $paiement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Paiement $paiement)
    {
        //
    }
    public function init()
    {

        $payerPhone = "674359141";
        $amount = "0.0015";
        $currency = "EUR";
        $externalId = "679064500";


        $payerMessage = "Test transaction";
        $payeeNote = "Test transaction aaa";

        $response = MoMoService::getApiKey();

        $apiKey = json_decode(json_decode($response, true)["response_body"],true)["apiKey"];

        $response = MoMoService::getAccessToken($apiKey);
        $accessToken = json_decode($response, true)["access_token"];

        $payer = new Payer("MSISDN",$payerPhone);
        $transactionReference = Uuid::uuid4()->toString();
        $momoTransaction = new MoMoTransaction(
            $amount,
            $currency,
            $externalId,
            $payer,
            $payerMessage,
            $payeeNote,
        );
        $response = MoMoService::requestToPay($accessToken, $momoTransaction,$transactionReference);
        return json_decode($response);




    }

    /**
     * Liste des paiements pour superviseur
     */
    public function pourSuperviseur(Request $request)
    {
        if (Auth::user()->role->libelle !== 'superviseur') {
            abort(403, 'Accès réservé aux superviseurs');
        }

        $query = Paiement::with(['apprenant', 'formation', 'caissier', 'auditeur'])
            ->orderBy('created_at', 'desc');

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        return response()->json($query->get());
    }

    /**
     * Détails d'un paiement
     */
    public function details(Paiement $paiement)
    {
        $paiement->load(['apprenant', 'formation', 'caissier', 'auditeur']);
        return response()->json($paiement);
    }

    /**
     * Rejeter un paiement
     */
    public function rejeter(Request $request, Paiement $paiement)
    {
        if (Auth::user()->role->libelle !== 'auditeur') {
            abort(403, 'Seuls les auditeurs peuvent rejeter des paiements.');
        }

        $request->validate([
            'motif' => 'required|string|max:255'
        ]);

        $paiement->update([
            'statut' => 'rejete',
            'motif' => $request->motif,
            'auditeur_id' => Auth::id(),
            'date_confirmation' => now()
        ]);

        return response()->json([
            'message' => 'Paiement rejeté avec succès',
            'paiement' => $paiement
        ]);
    }

    /**
     * Générer un reçu PDF
     */
    public function genererRecu(Paiement $paiement)
    {
        $paiement->load(['apprenant', 'formation', 'caissier']);
        
        $pdf = PDF::loadView('recu_paiement', [
            'paiement' => $paiement
        ]);

        return $pdf->stream('recu-paiement-'.$paiement->id.'.pdf');
    }

}
