<?php
namespace App\Services;

use App\Models\{Demande, DemandeHistorique, NotificationInterne, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WorkflowService
{
    /** Transitions permises depuis le statut courant, filtrées par les droits de l'utilisateur. */
    public function disponibles(Demande $d, User $u): array
    {
        $out = [];
        foreach (config("workflow.transitions.{$d->statut}", []) as $vers => [$perm, $motif, $label]) {
            if ($u->hasPermission($perm)) $out[$vers] = ['motif' => $motif, 'label' => $label];
        }
        return $out;
    }

    public function transition(Demande $demande, string $vers, ?User $u, ?string $motif = null, bool $visibleClient = false, bool $systeme = false): Demande
    {
        return DB::transaction(function () use ($demande, $vers, $u, $motif, $visibleClient, $systeme) {
            $d = Demande::with('candidat.session', 'candidat.examen')->whereKey($demande->id)->lockForUpdate()->firstOrFail();
            $regle = config("workflow.transitions.{$d->statut}.$vers");
            if (! $regle && ! ($systeme && $this->transitionSysteme($d->statut, $vers))) {
                throw ValidationException::withMessages(['statut' => "Transition interdite : « {$d->libelleStatut()} » vers « " . (config('workflow.statuts')[$vers] ?? $vers) . ' ».']);
            }
            if (! $systeme) {
                if (! $u || ! $u->actif || ! $u->hasPermission($regle[0])) throw new HttpException(403, "Vous n'avez pas le droit d'effectuer cette étape.");
                if ($regle[1] && blank($motif)) throw ValidationException::withMessages(['motif' => 'Un motif est obligatoire pour cette action.']);
            }
            $de = $d->statut;
            $this->controles($d, $de, $vers, $u);

            $maj = ['statut' => $vers];
            if ($vers === 'en_verification' && $de === 'attente_validation_admin' && config('workflow.retour_correction') === 'recalcul') {
                $maj['date_prevue'] = app(DelaiService::class)->ajouterJoursOuvres(now())->toDateString();
            }
            if ($vers === 'delivre') $maj['delivre_at'] = now();
            $d->update($maj);

            if ($de === 'attente_validation_admin' && $vers === 'en_verification') {
                $s = $d->candidat->session;
                $s->update(['verrouillee' => true, 'verrouillee_at' => now(), 'verrouillee_par' => $u?->id]);
                Audit::log('session.reverrouillee', $s, 'Validation de liste via dossier ' . $d->numero);
            }
            if ($vers === 'valide_impression') $this->preparerDiplome($d, $u);

            DemandeHistorique::create(['demande_id' => $d->id, 'user_id' => $u?->id, 'de' => $de, 'vers' => $vers, 'motif' => $motif, 'visible_client' => $visibleClient]);
            Audit::log('demande.transition', $d, $motif, ['statut' => $de], ['statut' => $vers]);
            $this->notifier($d, $vers, $visibleClient ? $motif : null);
            return $d;
        });
    }

    private function transitionSysteme(string $de, string $vers): bool
    {
        return ($de === 'valide_impression' && $vers === 'imprime') || ($de === 'attente_pieces' && $vers === 'recue');
    }

    private function controles(Demande $d, string $de, string $vers, ?User $u): void
    {
        $err = fn (string $m) => throw ValidationException::withMessages(['statut' => $m]);
        if ($vers === 'en_verification' && $de === 'recue' && ! $d->estComplete()) {
            $err('Dossier incomplet : pièces manquantes (' . implode(', ', $d->piecesManquantes()) . ').');
        }
        if ($vers === 'attente_signature') {
            if (! $d->candidat) $err("Associez d'abord le demandeur à un candidat de la liste officielle des admis.");
            if (! $d->candidat->session->verrouillee) $err("La liste de la session n'est pas validée et verrouillée par l'administrateur.");
        }
        if ($vers === 'en_verification' && $de === 'attente_validation_admin' && ! $d->candidat) {
            $err("Aucun candidat associé : validation impossible.");
        }
    }

    private function preparerDiplome(Demande $d, ?User $u): void
    {
        $svc = app(DiplomeService::class);
        $dip = $d->diplome ?: $svc->creer($d->candidat->load('examen'), $u);
        if ($dip->statut === 'brouillon') $svc->signer($dip, $u);
        $d->update(['diplome_id' => $dip->id]);
    }

    /** Appelée par DiplomeService après la première impression. */
    public function apresImpression(int $diplomeId): void
    {
        foreach (Demande::where('diplome_id', $diplomeId)->where('statut', 'valide_impression')->get() as $d) {
            $this->transition($d, 'imprime', null, 'Impression enregistrée', false, true);
        }
    }

    /** Appelée quand le client dépose une pièce : si le dossier devient complet, le délai démarre. */
    public function pieceAjoutee(Demande $d): void
    {
        $d->refresh();
        if ($d->estComplete() && ! $d->complet_at) {
            $d->update(['complet_at' => now(), 'date_prevue' => app(DelaiService::class)->ajouterJoursOuvres(now())->toDateString()]);
            if ($d->statut === 'attente_pieces') $this->transition($d, 'recue', null, 'Dossier complet', true, true);
        }
    }

    private function notifier(Demande $d, string $vers, ?string $motifClient): void
    {
        $label = config('workflow.statuts')[$vers] ?? $vers;
        $msg = "Dossier {$d->numero} : $label." . ($motifClient ? " Motif : $motifClient" : '');
        NotificationInterne::create(['user_id' => $d->user_id, 'titre' => 'Mise à jour de votre demande', 'message' => $msg, 'lien' => route('demandes.show', $d)]);

        $perms = collect(config("workflow.transitions.$vers", []))->map(fn ($r) => $r[0])->unique()->all();
        if (! $perms) return;
        $roles = collect(config('permissions.map'))->filter(fn ($p, $role) => $role !== 'super_admin' && array_intersect($p, $perms))->keys();
        User::whereIn('role', $roles)->where('actif', true)->limit(50)->pluck('id')->each(fn ($id) =>
            NotificationInterne::create(['user_id' => $id, 'titre' => 'Dossier à traiter', 'message' => "Dossier {$d->numero} : $label.", 'lien' => route('demandes.show', $d)]));
    }
}
