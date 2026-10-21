<?php
namespace App\Services;

use App\Models\{Candidat, Organisme};
use Illuminate\Support\Facades\RateLimiter;

class VerificationOrganismeService
{
    public const LIBELLES = [
        'admis_present' => 'Admis et présent dans la base officielle',
        'non_retrouve' => "Non retrouvé dans la base (ne vaut pas non-admission)",
        'donnees_insuffisantes' => 'Données insuffisantes pour conclure',
        'document_invalide' => 'Document invalide, révoqué ou remplacé',
    ];

    public function authentifier(string $cle, string $ip): ?Organisme
    {
        $k = 'orgkey|' . $ip;
        if (RateLimiter::tooManyAttempts($k, 5)) return null;
        $o = Organisme::where('cle_hash', hash('sha256', trim($cle)))->where('actif', true)->first();
        if (! $o) { RateLimiter::hit($k, 900); Audit::log('verif.cle_invalide', null, "ip $ip"); return null; }
        RateLimiter::clear($k);
        return $o;
    }

    /** @return array{statut:string,libelle:string,details:array}|null  null si la limite horaire est atteinte */
    public function verifier(Organisme $o, array $q): ?array
    {
        $cle = 'org|' . $o->id;
        if (RateLimiter::tooManyAttempts($cle, $o->limite_heure)) return null;
        RateLimiter::hit($cle, 3600);

        $nom = trim((string) ($q['nom'] ?? ''));
        $prenoms = trim((string) ($q['prenoms'] ?? ''));
        $date = trim((string) ($q['date_naissance'] ?? ''));
        $insc = trim((string) ($q['numero_inscription'] ?? ''));
        if ($nom === '' || ($date === '' && $insc === '')) return $this->res('donnees_insuffisantes');

        $req = Candidat::with('examen', 'session', 'diplome');
        $req->where('nom_normalise', $prenoms !== '' ? '=' : 'like', $prenoms !== '' ? Candidat::normaliser("$nom $prenoms") : Candidat::normaliser($nom) . ' %');
        if ($date !== '') $req->whereDate('date_naissance', $date);
        if ($insc !== '') $req->where('numero_inscription', $insc);
        if (! empty($q['examen'])) $req->whereHas('examen', fn ($e) => $e->where('code', $q['examen']));
        if (! empty($q['annee'])) $req->whereHas('session', fn ($s) => $s->where('annee', (int) $q['annee']));

        $trouves = $req->limit(2)->get();
        if ($trouves->isEmpty()) return $this->res('non_retrouve');
        if ($trouves->count() > 1) return $this->res('donnees_insuffisantes', ['remarque' => 'Plusieurs correspondances : précisez la date de naissance, le n° d\'inscription ou l\'examen.']);

        $c = $trouves->first();
        $d = $c->diplome;
        if ($d && in_array($d->statut, ['revoque', 'remplace'], true)) return $this->res('document_invalide', ['diplome' => $d->numero_diplome, 'statut' => $d->libelleStatut()]);
        return $this->res('admis_present', [
            'examen' => $c->examen->code, 'session' => $c->session->annee, 'titulaire' => mb_strtoupper($c->nom) . ' ' . $c->prenoms,
            'diplome' => $d && $d->date_signature ? $d->numero_diplome . ' (' . $d->libelleStatut() . ')' : 'Pas encore établi',
        ]);
    }

    public function journaliser(Organisme $o, int $nb): void
    {
        $o->update(['dernier_usage_at' => now()]);
        Audit::log('verif.organisme', null, "Organisme #{$o->id} {$o->nom} : $nb vérification(s)");
    }

    private function res(string $s, array $details = []): array { return ['statut' => $s, 'libelle' => self::LIBELLES[$s], 'details' => $details]; }
}
