<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Api\FormationController;

use App\Http\Controllers\Api\FormateurController;

use App\Http\Controllers\Api\SuperviseurController;

use App\Http\Controllers\Api\ApprenantController;

use App\Http\Controllers\Api\UtilisateurController;

use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\RoleController;

use App\Http\Controllers\Api\ProfilController;

use App\Http\Controllers\Api\InscriptionController;

use App\Http\Controllers\Api\ClasseController;

use App\Http\Controllers\Api\SeanceController;

use App\Http\Controllers\Api\PresenceController;

use App\Http\Controllers\Api\AuditeurController;

use App\Http\Controllers\Api\CaissierController;

use App\Http\Controllers\Api\VendeurController;

use App\Http\Controllers\Api\AdministrateurController;

use App\Http\Controllers\Api\ExamenController;

use App\Http\Controllers\Api\TentativeController;

use App\Http\Controllers\Api\PaiementController;

use App\Http\Controllers\Api\ParenteController;

use App\Http\Controllers\Api\TestController;

use App\Http\Controllers\Api\ImportController;
// Routes publiques
//Se connecter a la plateforme
Route::post('/login', [AuthController::class, 'login']);
//verifier l'email
Route::post('/email/verify', [UtilisateurController::class, 'verifierEmail']);

//mot de passe oublie
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
//restaurer le mot de passe
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    //crrer un utilisateur
    Route::post('/register', [AuthController::class, 'register']);
    //deconnexion des utilisateur
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    //modifier se identifiants de connexion
    Route::put('/modifier-identifiants', [AuthController::class, 'modifierIdentifiants']);

    // Utilisateurs
    Route::controller(UtilisateurController::class)->group(function () {
        Route::post('/utilisateurs/changer', 'changerMotDePasse');
        Route::get('/utilisateurs', 'index');
        Route::get('/utilisateurs/apprenants', 'listerApprenants');
        Route::put('/utilisateurs/{id}', 'update');
        Route::post('/utilisateurs/{id}', 'update');
        Route::post('/utilisateurs', 'store');

        // Activation/désactivation utilisateur (réservé aux admins)
        Route::patch('/utilisateurs/{id}/activer', 'activer');
        Route::patch('/utilisateurs/{id}/desactiver', 'desactiver');

        //recherche
        Route::get('/utilisateurs/rechercher',  'rechercher');
        Route::get('/utilisateurs/{id}', 'show');
        //exportation
        Route::get('/export/excel', 'exportExcel');
        Route::get('/export/pdf', 'exportPDFUtilisateurs');
    });

    // Administrateurs
    Route::prefix('administrateurs')->controller(AdministrateurController::class)->group(function () {
        Route::get('/', 'index');//lister tous les administrateurs
        Route::post('/', 'store');//ajouter un admin
        Route::get('/{id}', 'show');//voir un admin specifique
        Route::put('/{id}', 'update');//mettre a jour un admin
        //exportation
        Route::get('/export/excel', 'exportExcel');
        Route::get('/export/pdf', 'exportPDFAdministrateurs');

    });


    // Superviseurs
    Route::prefix('superviseurs')->controller(SuperviseurController::class)->group(function () {
        Route::get('/', 'index');//lister tous lles superviseurs
        Route::post('/', 'store');//ajouter un superviseur
        Route::get('/{id}', 'show');//voir un superviseur specifique
        Route::put('/{id}', 'update');//mettre a jour un superviseur
        //exportation
        Route::get('/export/excel', 'exportExcel');
        Route::get('/export/pdf', 'exportPDFSuperviseurs');

    });

    // Formateurs
    Route::prefix('formateurs')->controller(FormateurController::class)->group(function () {
        Route::get('/', 'index');//lister les formateurs
        Route::post('/', 'store');//ajouter un formateur
        Route::get('/{id}', 'show');//voir un formateur specifique
        Route::put('/{id}', 'update');//mettre a jour un formateur
        //exportation
        Route::get('/export/excel', 'exportExcel');
        Route::get('/export/pdf', 'exportPDFformateurs');

    });

    // Apprenants
    Route::prefix('apprenants')->controller(ApprenantController::class)->group(function () {
        Route::get('/', 'index');//lister les apprenants
        Route::post('/', 'store');//ajouter un apprenant
        Route::get('/{id}', 'show');//voir un apprenant specifique
        Route::put('/{id}', 'update');//mettre a jour un apprenant
        //exportation
        Route::get('/export/excel', 'exportExcel');
        Route::get('/export/pdf', 'exportPDFApprenants');

    });

    // Parents
    Route::prefix('parents')->controller(ParenteController::class)->group(function () {
        Route::get('/', 'index');//voir tous les parents
        Route::post('/', 'store');//ajouter un parent
        Route::get('/{id}', 'show');//voir un parent particulier
        Route::put('/{id}', 'update');//mettre a jour un parent
        Route::get('/export/excel', 'exportExcel');//exporter en excell
        Route::get('/export/pdf', 'exportPDFParents');//exporter en pdf
    });


    // Caissiers
    Route::prefix('caissierss')->controller(CaissierController::class)->group(function () {
        Route::get('/', 'index');//afficher les caissiers
        Route::post('/', 'store');//ajouter un caissier
        Route::get('/{id}', 'show');//voir un caissier particulier
        Route::put('/{id}', 'update');//mettre a jour les informations d'un caissier
        Route::get('/export/excel', 'exportExcel');//exporter la liste des caissiers en excell
        Route::get('/export/pdf', 'exportPDFCaissiers');//exporter la liste des caissiers en pdf

    });

    // Auditeurs
    Route::prefix('auditeurs')->controller(AuditeurController::class)->group(function () {
        Route::get('/', 'index');//afficher tous les auditeurs
        Route::post('/', 'store');//ajouter un auditeur
        Route::get('/{id}', 'show');//voir un auditeur particulier
        Route::put('/{id}', 'update');//mettre a jour un auditeur
        Route::get('/export/excel', 'exportExcel');//exporter en excell
        Route::get('/export/pdf', 'exportPDFAuditeurs');//exporter en pdf

    });

    // Vendeurs
    Route::prefix('vendeurs')->controller(VendeurController::class)->group(function () {
        Route::get('/', 'index');//afficher tous les vendeurs
        Route::post('/', 'store');//enregistrer un vendeur
        Route::get('/{id}', 'show');//voir un vendeur specifique
        Route::put('/{id}', 'update');//mettre a jour un vendeur
        Route::get('/export/excel', 'exportExcel');//exporter la liste des vendeurs en excell
        Route::get('/export/pdf', 'exportPDFVendeurs');//exporter la liste des vendeurs en pdf

    });

    //Formations
    Route::controller(FormationController::class)->group(function () {
        Route::get('/mes-formation', 'mesFormations');//permettre au formateur de voir ses formations
        Route::get('/formations/pdf',  'exportPdf');//exporter la liste des formations au format pdf
        Route::get('/formations/search', 'search');//rechercher une formation
        Route::get('/formations/{id}/apprenants', 'getApprenants');//afficher les apprenants inscrits a une formation
        Route::post('/formations/{id}/assign-formateurs', 'assignFormateurs');//assigner un formateur a une formation
        Route::get('/formations/{id}', 'show');//voir une formation specifique
        Route::get('/formations', 'index');//afficher toutes les formations
        Route::delete('/formations/{id}', 'destroy');//supprimer une formation
        Route::put('/formations/{id}', 'update');//mettre une formation a jour
        Route::post('/formations', 'store');//creer une formation

    });

    //Classes
    Route::controller(ClasseController::class)->group(function () {
        Route::get('/classes/{id}', 'show');//afficher une classe particuliere
        Route::get('/classes', 'index');//voir toutes les classes
        Route::put('/classes/{id}', 'update');//mettre a jour une classe
        Route::post('/classes', 'store');//creer une classe

        Route::post('/classes/{id}/verifier-disponibilite', 'verifierDisponibilite');//verifier la disponibilite
        Route::get('/classes/{id}/capacite-restante', 'getCapaciteRestante');//voir la capacite restante
    });

    //Seances
    Route::controller(SeanceController::class)->group(function () {
        Route::get('/seances/{seance}', 'show');//afficher une seance particuliere
        Route::get('/seances', 'index');//afficher toutes les seances
        Route::put('/seances/{id}', 'update');//Mettre une seance a jour
        Route::post('/seances', 'store');//ajouter une seance

        Route::post('/seances/{id}/annuler', 'annulerSeance');
        Route::post('/seances/{id}/demarrer', 'demarrerSeance');
        Route::post('/seances/{id}/terminer', 'terminerSeance');
        Route::post('/seances/{id}/notifier', 'notifierParticipants');
        Route::get('/seances/{id}/feuille-presence', 'genererFeuillePresence');
        Route::get('/seances/{id}/formateur', 'obtenirFormateur');
        Route::get('/seances/{id}/classe', 'obtenirClasse');
        Route::delete('/seances/{seance}', 'destroy');


    });

    Route::get('/mes-notifications', [NotificationController::class, 'index']);
    Route::post('/mes-notifications/{id}/marquer-comme-lu', [NotificationController::class, 'markAsRead']);

    //Presences
    Route::controller(PresenceController::class)->group(function () {
        Route::get('/seance/{seance_id}', 'listePresenceParSeance');//afficher la liste de presence pour une seance
        Route::get('/presence/date', 'presencesParDate');//afficher les presences par date
        Route::get('/apprenant/{apprenant_id}', 'listePresenceParApprenant');//afficher les differntes presences d'un apprenant
        Route::post('/marquer', 'marquerPresence');//marquer les presences
        Route::post('/justifier', 'justifierAbsence');//justifier les abscences
        Route::get('/pdf/{seance_id}', 'exporterPDF');//exporter la liste de presence en pdf
        Route::get('/csv/{seance_id}', 'exporterCSV');//exporter la liste de presence au format csv
    });

    //inscriptions
    Route::controller(InscriptionController::class)->group(function () {
        Route::get('/inscriptions/recherche',  'recherche');
        Route::get('/mes-inscriptions', 'mesInscriptions'); // inscription faite par l'admin ou le superviseur
        Route::get('/mes-enfants/inscriptions', 'inscriptionsParent'); //
        Route::get('/mes-formations',  'inscriptionsApprenant'); //formation ou l'apprenant est inscrit
        Route::post('/formations/{id}/inscrire', 'inscrire');
    });

    Route::controller(ExamenController::class)->group(function () {
        // Lister tous les examens d'un module
        Route::get('modules/{module}/examens', 'indexForModule');

        // Créer un nouvel examen pour un module
        Route::post('modules/{module}/examens', 'storeForModule');

        // Afficher un examen spécifique (indépendant du module, on a juste besoin de son ID)
        Route::get('examens/{examen}',  'show');

        // Mettre à jour un examen spécifique
        Route::put('examens/{examen}', 'update');

        // Archiver un examen
        Route::patch('examens/{examen}/archive', 'archive');
    });

    //voir les reponses d'un test
    Route::post('tests/{examen}/check', [TestController::class, 'checkAnswers']);

    //apprenant commence a composer un examen
    Route::post('examens/{examen}/start', [TentativeController::class, 'start']);
    // Soumettre les réponses pour une tentative spécifique
    Route::post('tentatives/{tentative}/submit', [TentativeController::class, 'submit']);
    //lister les tentatives pour une evaluation
    Route::get('evaluations/{examen}/tentatives', [TentativeController::class, 'indexForExamen']);

   // avoir les statistiques d'une evaluation
    Route::get('evaluations/{examen}/statistiques', [TentativeController::class, 'getStatistiques']);
    //importer un examen
    Route::post('modules/{module}/import-examen', [ImportController::class, 'importExamen']);


     // Paiements
    Route::post('/paiements/initier', [PaiementController::class, 'initier']);
    Route::post('/paiements/{paiement}/confirmer', [PaiementController::class, 'confirmer']);
    Route::post('/paiements/{paiement}/rejeter', [PaiementController::class, 'rejeter']);
    Route::get('/paiements/superviseur', [PaiementController::class, 'pourSuperviseur']);
    Route::get('/paiements/{paiement}', [PaiementController::class, 'details']);
    Route::get('/paiements/{paiement}/recu', [PaiementController::class, 'genererRecu']);


});
Route::post('paiements/init', [PaiementController::class, 'init']);

