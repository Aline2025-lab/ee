<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\EnvoiIdentifiants;

use App\Http\Controllers\Api\UtilisateurController;

Route::get('/email/verify/{id}/{code}', [UtilisateurController::class, 'verifierEmailWeb'])->name('verification.email');

Route::get('/test-mail', function () {
    $user = \App\Models\User::first();
    Mail::to($user->email)->send(new EnvoiIdentifiants($user, 'MotDePasseTest123'));
    return 'Mail envoyé !';
});

Route::get('/', function () {
    return view('welcome');
});


