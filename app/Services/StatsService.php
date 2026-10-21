<?php
namespace App\Services;

use App\Models\{Attestation, AutorisationReimpression, Demande, Diplome, Impression};
use Illuminate\Support\Facades\DB;

class StatsService
{
    // Les délais utilisent TIMESTAMPDIFF : MySQL / MariaDB uniquement.
    public function calculer(array $f): array
    {
        $q = Demande::query();
        if (! empty($f['du'])) $q->whereDate('recu_at', '>=', $f['du']);
        if (! empty($f['au'])) $q->whereDate('recu_at', '<=', $f['au']);
        foreach (['examen_id', 'session_id', 'statut'] as $k) if (! empty($f[$k])) $q->where($k, $f[$k]);
        $sub = (clone $q)->select('demandes_diplomes.id');

        $date = function ($builder, string $col) use ($f) {
            if (! empty($f['du'])) $builder->whereDate($col, '>=', $f['du']);
            if (! empty($f['au'])) $builder->whereDate($col, '<=', $f['au']);
            return $builder;
        };
        $enCours = ['delivre', 'rejete', 'archive', 'imprime', 'disponible_retrait'];

        $delaiH = DB::table('demande_historique as h')->join('demandes_diplomes as d', 'd.id', '=', 'h.demande_id')
            ->where('h.vers', 'imprime')->whereIn('h.demande_id', $sub)->avg(DB::raw('TIMESTAMPDIFF(HOUR, d.recu_at, h.created_at)'));

        return [
            'total' => (clone $q)->count(),
            'par_statut' => (clone $q)->selectRaw('statut, count(*) n')->groupBy('statut')->pluck('n', 'statut'),
            'par_examen' => (clone $q)->join('examens as ex', 'ex.id', '=', 'demandes_diplomes.examen_id')->selectRaw('ex.code as lib, count(*) n')->groupBy('ex.code')->pluck('n', 'lib'),
            'par_session' => (clone $q)->join('sessions_examens as s', 's.id', '=', 'demandes_diplomes.session_id')->join('examens as ex', 'ex.id', '=', 'demandes_diplomes.examen_id')
                ->selectRaw("CONCAT(ex.code, ' ', s.annee) as lib, count(*) n")->groupBy('s.id', 'ex.code', 's.annee')->pluck('n', 'lib'),
            'delai_moyen_jours' => $delaiH === null ? null : round($delaiH / 24, 1),
            'hors_delai' => (clone $q)->whereDate('date_prevue', '<', today())->whereNotIn('statut', $enCours)->count(),
            'par_agent' => DB::table('demande_historique as h')->join('users as u', 'u.id', '=', 'h.user_id')->whereIn('h.demande_id', $sub)
                ->selectRaw('u.name, u.role, count(*) n')->groupBy('u.id', 'u.name', 'u.role')->orderByDesc('n')->limit(30)->get(),
            'retours' => DB::table('demande_historique')->whereIn('demande_id', $sub)->where('vers', 'attente_correction')->count(),
            'motifs' => DB::table('demande_historique')->whereIn('demande_id', $sub)->where('vers', 'attente_correction')->whereNotNull('motif')
                ->selectRaw('motif, count(*) n')->groupBy('motif')->orderByDesc('n')->limit(15)->get(),
            'diplomes_valides' => $date(Diplome::whereNotNull('date_signature'), 'date_signature')->count(),
            'diplomes_imprimes' => $date(Impression::where('rang', 1), 'imprime_at')->count(),
            'reimpressions' => $date(AutorisationReimpression::query(), 'created_at')->count(),
            'attestations' => $date(Attestation::query(), 'emise_at')->selectRaw('type, count(*) n')->groupBy('type')->pluck('n', 'type'),
        ];
    }
}
