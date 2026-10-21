<?php
namespace App\Services;

use App\Models\{AutorisationReimpression, Candidat, Diplome, Impression, SessionExamen, User};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DiplomeService
{
    /** Crée un diplôme en brouillon. Numéro séquentiel par session, sous verrou. */
    public function creer(Candidat $candidat, User $user): Diplome
    {
        return DB::transaction(function () use ($candidat, $user) {
            $session = SessionExamen::whereKey($candidat->session_id)->lockForUpdate()->first();
            if (Diplome::withTrashed()->where('candidat_id', $candidat->id)->exists()) {
                throw ValidationException::withMessages(['candidat' => 'Un diplôme existe déjà pour ce candidat.']);
            }
            $prefixe = config('diplomes.examens')[$candidat->examen->code]['prefixe'] ?? 'X';
            $seq = Diplome::withTrashed()->whereHas('candidat', fn ($q) => $q->where('session_id', $session->id))->count() + 1;
            $numero = sprintf('%s %05d/%s', $prefixe, $seq, substr((string) $session->annee, -2));

            $d = Diplome::create([
                'candidat_id' => $candidat->id,
                'numero_diplome' => $numero,
                'statut' => 'brouillon',
                'lieu_signature' => config('diplomes.lieu_signature'),
                'token_verification' => bin2hex(random_bytes(24)),
                'cree_par' => $user->id,
            ]);
            Audit::log('diplome.cree', $d, null, null, ['numero' => $numero]);
            return $d;
        });
    }

    /** Validation définitive : la date de signature est posée par le serveur, jamais saisie. */
    public function signer(Diplome $diplome, User $user): Diplome
    {
        if (! $user->hasPermission('diplomes.signer')) {
            throw new HttpException(403, "Vous n'avez pas le droit de valider.");
        }
        return DB::transaction(function () use ($diplome, $user) {
            $d = Diplome::whereKey($diplome->id)->lockForUpdate()->firstOrFail();
            if ($d->statut !== 'brouillon') {
                throw ValidationException::withMessages(['statut' => 'Ce diplôme a déjà été validé.']);
            }
            $d->update(['statut' => 'valide_pour_impression', 'date_signature' => now(), 'signe_par' => $user->id]);
            Audit::log('diplome.signe', $d, null, ['statut' => 'brouillon'], ['statut' => $d->statut, 'date_signature' => $d->date_signature->toDateTimeString()]);
            return $d;
        });
    }

    public function autoriserReimpression(Diplome $diplome, User $user, string $motif): AutorisationReimpression
    {
        if (! $user->isSuperAdmin()) {
            throw new HttpException(403, 'Seul le super administrateur peut autoriser une réimpression.');
        }
        if ($diplome->statut !== 'imprime') {
            throw ValidationException::withMessages(['motif' => "Une réimpression n'est possible que pour un diplôme déjà imprimé."]);
        }
        if ($diplome->autorisationDisponible()) {
            throw ValidationException::withMessages(['motif' => 'Une autorisation de réimpression est déjà en attente.']);
        }
        $a = AutorisationReimpression::create(['diplome_id' => $diplome->id, 'motif' => $motif, 'autorise_par' => $user->id]);
        Audit::log('diplome.reimpression_autorisee', $diplome, $motif);
        return $a;
    }

    /**
     * Imprime sous verrou de ligne. Le PDF est généré AVANT l'enregistrement : s'il échoue, rien n'est consommé.
     * Retourne le contenu PDF.
     */
    public function imprimer(Diplome $diplome, User $user): string
    {
        if (! $user->hasPermission('diplomes.imprimer')) {
            throw new HttpException(403, "Vous n'avez pas le droit d'imprimer.");
        }
        return DB::transaction(function () use ($diplome, $user) {
            $d = Diplome::with('candidat.session.examen')->whereKey($diplome->id)->lockForUpdate()->firstOrFail();
            $rang = 1;
            $autorisation = null;

            if ($d->nb_impressions === 0) {
                if ($d->statut !== 'valide_pour_impression') {
                    throw new HttpException(403, "Ce diplôme n'est pas validé pour impression.");
                }
            } else {
                $autorisation = AutorisationReimpression::where('diplome_id', $d->id)->whereNull('utilisee_at')->lockForUpdate()->oldest()->first();
                if (! $autorisation) {
                    throw new HttpException(403, 'Diplôme déjà imprimé : une réimpression doit être autorisée par le super administrateur.');
                }
                $rang = $d->nb_impressions + 1;
            }

            $pdf = $this->rendrePdf($d, $rang);

            Impression::create(['diplome_id' => $d->id, 'rang' => $rang, 'user_id' => $user->id, 'autorisation_id' => $autorisation?->id, 'ip' => request()?->ip(), 'imprime_at' => now()]);
            if ($autorisation) {
                $autorisation->update(['utilisee_at' => now()]);
            }
            $d->update(['nb_impressions' => $rang, 'statut' => 'imprime']);
            Audit::log($rang === 1 ? 'diplome.imprime' : 'diplome.reimprime', $d, $autorisation?->motif, null, ['rang' => $rang]);
            if ($rang === 1) app(WorkflowService::class)->apresImpression($d->id);
            return $pdf;
        });
    }

    /** Aperçu filigrané "SPÉCIMEN" : n'enregistre rien et ne consomme aucune impression. */
    public function apercu(Diplome $d): string
    {
        return $this->rendrePdf($d->loadMissing('candidat.session.examen'), 1, true);
    }

    public function rendrePdf(Diplome $d, int $rang = 1, bool $specimen = false): string
    {
        $c = $d->candidat;
        $reg = config('diplomes.examens')[$c->examen->code] ?? [];
        $url = url('/verifier/' . $d->token_verification);
        $qr = Qr::dataUri($url);

        return Pdf::loadView('pdf.diplome', [
            'd' => $d, 'c' => $c, 'reg' => $reg, 'session' => $c->session, 'qr' => $qr, 'url' => $url,
            'rang' => $rang, 'specimen' => $specimen, 'directeur' => config('diplomes.directeur_nom'),
        ])->setPaper('a4', 'landscape')->output();
    }
}
