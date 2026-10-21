<?php
namespace App\Services;

use App\Models\{Attestation, Candidat, User};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AttestationService
{
    /** Pour chaque type : null si émission possible, sinon la raison du refus. */
    public function eligibilite(Candidat $c): array
    {
        $c->loadMissing('session', 'diplome');
        $res = [];
        $res['perte'] = ($c->diplome && in_array($c->diplome->statut, ['imprime', 'delivre'], true)) ? null
            : "Aucun diplôme déjà imprimé ou délivré pour ce candidat : une attestation de perte n'a pas lieu d'être.";
        $res['provisoire'] = ! $c->session->verrouillee ? null
            : "Le résultat est déjà approuvé (liste verrouillée) : le diplôme doit être établi, pas une attestation provisoire.";
        $res['reussite'] = ! config('attestations.reussite_autorisee') ? "Ce type n'est pas autorisé par le règlement configuré (DIPLOMES_ATTESTATION_REUSSITE)."
            : ($c->session->verrouillee ? null : "Résultat non encore officiellement approuvé : aucune attestation de réussite possible.");
        return $res;
    }

    public function emettre(Candidat $c, string $type, User $u, ?string $reference): Attestation
    {
        if (! $u->hasPermission('attestations.emettre')) throw new HttpException(403, "Vous n'avez pas le droit d'émettre des attestations.");
        if (! array_key_exists($type, config('attestations.types'))) throw ValidationException::withMessages(['type' => 'Type inconnu.']);
        $refus = $this->eligibilite($c)[$type] ?? 'Type inconnu.';
        if ($refus) throw ValidationException::withMessages(['type' => $refus]);
        if ($type === 'perte' && mb_strlen(trim((string) $reference)) < 5) {
            throw ValidationException::withMessages(['reference' => 'Indiquez la référence de la déclaration de perte.']);
        }

        return DB::transaction(function () use ($c, $type, $u, $reference) {
            $anciennes = Attestation::where('candidat_id', $c->id)->where('type', $type)->where('statut', 'valide')->lockForUpdate()->get();
            $a = Attestation::create([
                'numero' => 'TMP' . bin2hex(random_bytes(6)), 'type' => $type, 'candidat_id' => $c->id, 'diplome_id' => $c->diplome?->id,
                'reference' => $reference, 'token_verification' => bin2hex(random_bytes(24)), 'emise_par' => $u->id, 'emise_at' => now(),
                'valide_jusqu' => $type === 'provisoire' ? now()->addMonths((int) config('attestations.validite_provisoire_mois'))->toDateString() : null,
            ]);
            $a->update(['numero' => sprintf('ATT%s-%05d', now()->format('y'), $a->id)]);
            foreach ($anciennes as $old) {
                $old->update(['statut' => 'remplace']);
                Audit::log('attestation.remplacee', $old, "Remplacée par {$a->numero}");
            }
            Audit::log('attestation.emise', $a, $reference, null, ['type' => $type, 'candidat_id' => $c->id]);
            return $a;
        });
    }

    public function revoquer(Attestation $a, User $u, string $motif): void
    {
        if (! $u->hasPermission('attestations.revoquer')) throw new HttpException(403, 'Droit insuffisant.');
        if ($a->statut !== 'valide') throw ValidationException::withMessages(['motif' => 'Cette attestation est déjà invalide.']);
        $a->update(['statut' => 'revoque', 'motif_revocation' => $motif]);
        Audit::log('attestation.revoquee', $a, $motif);
    }

    public function pdf(Attestation $a): string
    {
        $a->loadMissing('candidat.session.examen', 'candidat.diplome');
        $url = url('/verifier/' . $a->token_verification);
        Audit::log('attestation.pdf', $a);
        return Pdf::loadView('pdf.attestation', ['a' => $a, 'c' => $a->candidat, 'qr' => Qr::dataUri($url), 'url' => $url,
            'reg' => config('diplomes.examens')[$a->candidat->examen->code] ?? []])->setPaper('a4')->output();
    }
}
