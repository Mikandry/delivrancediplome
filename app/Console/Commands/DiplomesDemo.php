<?php
namespace App\Console\Commands;

use App\Models\{Candidat, Examen, SessionExamen};
use Illuminate\Console\Command;

class DiplomesDemo extends Command
{
    protected $signature = 'diplomes:demo';
    protected $description = 'Crée des données FICTIVES de démonstration (ne jamais lancer en production).';

    public function handle(): int
    {
        if (app()->isProduction()) { $this->error('Refusé en production.'); return self::FAILURE; }
        $e = Examen::where('code', 'BEPC')->firstOrFail();
        $s = SessionExamen::firstOrCreate(['examen_id' => $e->id, 'annee' => 2026], ['libelle' => 'Session 2026 (DÉMO)', 'pv_date' => '2026-08-08']);
        foreach ([['DEMO', 'Candidat Un', '2010-12-02', 'Toamasina'], ['EXEMPLE', 'Fictif Deux', '2011-03-15', 'Antsirabe']] as $i => [$n, $p, $dn, $ln]) {
            Candidat::firstOrCreate(['session_id' => $s->id, 'numero_inscription' => 'DEMO-' . ($i + 1)], [
                'examen_id' => $e->id, 'nom' => $n, 'prenoms' => $p, 'date_naissance' => $dn, 'lieu_naissance' => $ln, 'sexe' => 'M',
                'numero_candidat' => 'FICTIF-' . ($i + 1), 'centre_ecrit' => 'CEG DÉMO', 'lycee_siegeant' => 'Lycée Démo Ampitatafika']);
        }
        $this->info('Données fictives créées (candidats DEMO / EXEMPLE).');
        return self::SUCCESS;
    }
}
