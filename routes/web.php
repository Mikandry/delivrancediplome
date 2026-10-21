<?php

use App\Http\Controllers\{AttestationController, AuditController, OrganismeController, RegistreController, SauvegardeController, StatsController, VerificationOrganismeController, AuthController, CandidatController, DashboardController, DemandeController, DiplomeController, ImportController, JourFerieController, NotificationController, RegisterController, SessionExamenController, SuiviController, UserController, VerificationController};
use App\Http\Middleware\{ForcePasswordChange, Permission};
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::get('/verifier/{token}', [VerificationController::class, 'show'])->middleware('throttle:30,1')->name('verification');

Route::get('/verification-organisme', [VerificationOrganismeController::class, 'form'])->name('organisme.form');
Route::post('/verification-organisme', [VerificationOrganismeController::class, 'verifier'])->middleware('throttle:20,1')->name('organisme.verifier');
Route::get('/suivi', [SuiviController::class, 'form'])->name('suivi');
Route::post('/suivi', [SuiviController::class, 'consulter'])->middleware('throttle:10,1')->name('suivi.consulter');

Route::middleware('guest')->group(function () {
    Route::get('/inscription', [RegisterController::class, 'show'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->middleware('throttle:20,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');
    Route::get('/mot-de-passe', [AuthController::class, 'editPassword'])->name('password.edit');
    Route::put('/mot-de-passe', [AuthController::class, 'updatePassword'])->name('password.update');

    Route::middleware(ForcePasswordChange::class)->group(function () {
        Route::get('/tableau-de-bord', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

        // Lot 3
        Route::middleware(Permission::class . ':attestations.view')->group(function () {
            Route::get('/attestations', [AttestationController::class, 'index'])->name('attestations.index');
            Route::get('/attestations/{attestation}/pdf', [AttestationController::class, 'pdf'])->name('attestations.pdf');
        });
        Route::middleware(Permission::class . ':attestations.emettre')->group(function () {
            Route::get('/attestations/candidat/{candidat}', [AttestationController::class, 'create'])->name('attestations.create');
            Route::post('/attestations/candidat/{candidat}', [AttestationController::class, 'store'])->name('attestations.store');
        });
        Route::post('/attestations/{attestation}/revoquer', [AttestationController::class, 'revoquer'])->middleware(Permission::class . ':attestations.revoquer')->name('attestations.revoquer');

        Route::middleware(Permission::class . ':registres.gerer')->group(function () {
            Route::get('/registres', [RegistreController::class, 'index'])->name('registres.index');
            Route::get('/registres/candidat/{candidat}', [RegistreController::class, 'create'])->name('registres.create');
            Route::post('/registres/candidat/{candidat}', [RegistreController::class, 'store'])->name('registres.store');
            Route::get('/registres/{registre}', [RegistreController::class, 'show'])->name('registres.show');
            Route::get('/registres/{registre}/modifier', [RegistreController::class, 'edit'])->name('registres.edit');
            Route::put('/registres/{registre}', [RegistreController::class, 'update'])->name('registres.update');
            Route::get('/registres/{registre}/pdf', [RegistreController::class, 'pdf'])->name('registres.pdf');
        });

        Route::get('/statistiques', [StatsController::class, 'index'])->middleware(Permission::class . ':stats.view')->name('stats.index');
        Route::get('/journal-audit', [AuditController::class, 'index'])->middleware(Permission::class . ':audit.view')->name('audit.index');

        Route::middleware(Permission::class . ':organismes.manage')->group(function () {
            Route::get('/organismes', [OrganismeController::class, 'index'])->name('organismes.index');
            Route::post('/organismes', [OrganismeController::class, 'store'])->name('organismes.store');
            Route::post('/organismes/{organisme}/basculer', [OrganismeController::class, 'toggle'])->name('organismes.toggle');
        });
        Route::middleware(Permission::class . ':sauvegardes.manage')->group(function () {
            Route::get('/sauvegardes', [SauvegardeController::class, 'index'])->name('sauvegardes.index');
            Route::post('/sauvegardes', [SauvegardeController::class, 'store'])->name('sauvegardes.store');
            Route::get('/sauvegardes/{nom}', [SauvegardeController::class, 'download'])->name('sauvegardes.download');
        });

        // Sessions
        Route::middleware(Permission::class . ':sessions.manage')->group(function () {
            Route::get('/sessions', [SessionExamenController::class, 'index'])->name('sessions.index');
            Route::post('/sessions', [SessionExamenController::class, 'store'])->name('sessions.store');
            Route::put('/sessions/{session}', [SessionExamenController::class, 'update'])->name('sessions.update');
        });
        Route::post('/sessions/{session}/verrouiller', [SessionExamenController::class, 'verrouiller'])->middleware(Permission::class . ':sessions.verrouiller')->name('sessions.verrouiller');
        Route::post('/sessions/{session}/deverrouiller', [SessionExamenController::class, 'deverrouiller'])->middleware(Permission::class . ':sessions.deverrouiller')->name('sessions.deverrouiller');

        // Imports
        Route::middleware(Permission::class . ':imports.create')->group(function () {
            Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
            Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
            Route::get('/imports/modele/{format}', [ImportController::class, 'modele'])->name('imports.modele');
            Route::get('/imports/{import}', [ImportController::class, 'show'])->name('imports.show');
            Route::post('/imports/{import}/confirmer', [ImportController::class, 'confirmer'])->name('imports.confirmer');
            Route::post('/imports/{import}/annuler', [ImportController::class, 'annuler'])->name('imports.annuler');
        });

        // Jours fériés
        Route::middleware(Permission::class . ':feries.manage')->group(function () {
            Route::get('/jours-feries', [JourFerieController::class, 'index'])->name('feries.index');
            Route::post('/jours-feries', [JourFerieController::class, 'store'])->name('feries.store');
            Route::delete('/jours-feries/{ferie}', [JourFerieController::class, 'destroy'])->name('feries.destroy');
        });

        // Demandes (client et personnel ; les droits sont revérifiés dans le contrôleur)
        Route::get('/demandes', [DemandeController::class, 'index'])->name('demandes.index');
        Route::get('/demandes/nouvelle', [DemandeController::class, 'create'])->name('demandes.create');
        Route::post('/demandes', [DemandeController::class, 'store'])->name('demandes.store');
        Route::get('/demandes/{demande}', [DemandeController::class, 'show'])->name('demandes.show');
        Route::post('/demandes/{demande}/pieces', [DemandeController::class, 'ajouterPiece'])->name('demandes.pieces');
        Route::get('/pieces/{piece}', [DemandeController::class, 'piece'])->name('pieces.show');
        Route::get('/demandes/{demande}/quittance', [DemandeController::class, 'quittance'])->name('demandes.quittance');
        Route::post('/demandes/{demande}/associer', [DemandeController::class, 'associer'])->name('demandes.associer');
        Route::post('/demandes/{demande}/transition', [DemandeController::class, 'transition'])->name('demandes.transition');

        Route::middleware(Permission::class . ':users.manage')->group(function () {
            Route::get('/utilisateurs', [UserController::class, 'index'])->name('users.index');
            Route::post('/utilisateurs', [UserController::class, 'store'])->name('users.store');
            Route::put('/utilisateurs/{user}', [UserController::class, 'update'])->name('users.update');
            Route::post('/utilisateurs/{user}/basculer', [UserController::class, 'toggle'])->name('users.toggle');
        });

        Route::middleware(Permission::class . ':candidats.view')->get('/candidats', [CandidatController::class, 'index'])->name('candidats.index');
        Route::middleware(Permission::class . ':candidats.create')->group(function () {
            Route::get('/candidats/nouveau', [CandidatController::class, 'create'])->name('candidats.create');
            Route::post('/candidats', [CandidatController::class, 'store'])->name('candidats.store');
        });
        Route::middleware(Permission::class . ':candidats.edit')->group(function () {
            Route::get('/candidats/{candidat}/modifier', [CandidatController::class, 'edit'])->name('candidats.edit');
            Route::put('/candidats/{candidat}', [CandidatController::class, 'update'])->name('candidats.update');
        });

        Route::middleware(Permission::class . ':diplomes.view')->group(function () {
            Route::get('/diplomes', [DiplomeController::class, 'index'])->name('diplomes.index');
            Route::get('/diplomes/{diplome}', [DiplomeController::class, 'show'])->name('diplomes.show');
            Route::get('/diplomes/{diplome}/apercu', [DiplomeController::class, 'apercu'])->name('diplomes.apercu');
        });
        Route::post('/diplomes', [DiplomeController::class, 'store'])->middleware(Permission::class . ':diplomes.create')->name('diplomes.store');
        Route::post('/diplomes/{diplome}/signer', [DiplomeController::class, 'signer'])->middleware(Permission::class . ':diplomes.signer')->name('diplomes.signer');
        Route::post('/diplomes/{diplome}/imprimer', [DiplomeController::class, 'imprimer'])->middleware(Permission::class . ':diplomes.imprimer')->name('diplomes.imprimer');
        Route::post('/diplomes/{diplome}/reimpression', [DiplomeController::class, 'autoriserReimpression'])->middleware(Permission::class . ':diplomes.reimpression')->name('diplomes.reimpression');
    });
});
