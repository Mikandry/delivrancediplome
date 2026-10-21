<?php
namespace App\Console\Commands;

use App\Models\{Attestation, Candidat, Demande, DemandeHistorique, Diplome, Examen, NotificationInterne, PieceDossier, RegistreNotes, SessionExamen, User};
use App\Services\{DelaiService, DiplomeService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Crypt, DB, Storage};

class DiplomesDemoMasse extends Command
{
    protected $signature = 'diplomes:demo-masse {--candidats=30 : candidats par session} {--demandes=25 : nombre de demandes} {--purge : supprime d\'abord les données de démonstration}';
    protected $description = 'Génère des candidats, diplômes et demandes ALÉATOIRES ET FICTIFS sur 3 sessions (jamais en production).';

    private const SESSIONS = [['BEPC', 2024, true], ['CAP.EP', 2025, true], ['CAE.EP', 2025, false]];   // [examen, année, verrouillée]
    private const NOMS = ['Rakoto', 'Rasoa', 'Randria', 'Rabe', 'Andrianina', 'Ravelo', 'Rajaona', 'Razafy', 'Rakotomalala', 'Rasolofo', 'Ramanana', 'Randrianasolo', 'Rafanomezana', 'Ratsimba', 'Raharison', 'Andriamanana', 'Razanakoto', 'Rajaonarison', 'Ranaivo', 'Rakotondrabe'];
    private const PRENOMS_M = ['Hery', 'Mamy', 'Faly', 'Tahina', 'Nirina', 'Santatra', 'Toky', 'Fanilo', 'Mickael', 'Jean Claude', 'Andry', 'Lalatiana'];
    private const PRENOMS_F = ['Tiana', 'Mialy', 'Voahangy', 'Haingo', 'Fenitra', 'Lalaina', 'Aina', 'Miora', 'Sarobidy', 'Marie Claire', 'Nirina', 'Soa'];
    private const LIEUX = ['Antananarivo', 'Toamasina', 'Antsirabe', 'Fianarantsoa', 'Mahajanga', 'Toliara', 'Antsiranana', 'Ambositra', 'Morondava', 'Manakara'];
    private const MOTIFS = ['Date de naissance différente de la liste officielle', "Orthographe du nom à vérifier", 'Lieu de naissance illisible sur la pièce', 'Pièce d\'identité périmée'];

    public function handle(DelaiService $delai, DiplomeService $dipSvc): int
    {
        if (app()->isProduction()) { $this->error('Refusé en production.'); return self::FAILURE; }
        $admin = User::where('role', 'super_admin')->first();
        if (! $admin) { $this->error("Aucun super administrateur : lancez d'abord php artisan diplomes:install"); return self::FAILURE; }
        if ($this->option('purge')) { $this->purger(); $this->info('Anciennes données de démonstration supprimées.'); }

        // 1. Sessions
        $sessions = []; $aVerrouiller = [];
        foreach (self::SESSIONS as [$code, $annee, $verrou]) {
            $examen = Examen::firstOrCreate(['code' => $code], ['intitule' => config("diplomes.examens.$code.intitule", $code), 'actif' => true]);
            $s = SessionExamen::where('examen_id', $examen->id)->where('annee', $annee)->first();
            if ($s && $s->libelle !== 'DÉMO (fictive)') { $this->warn("Session $code $annee existante et non fictive : ignorée."); continue; }
            $s ??= SessionExamen::create(['examen_id' => $examen->id, 'annee' => $annee, 'libelle' => 'DÉMO (fictive)', 'pv_date' => "$annee-08-08", 'pv_reference' => "PV-DEMO-$annee"]);
            $sessions[] = $s->load('examen');
            $aVerrouiller[$s->id] = $verrou;
        }
        if (! $sessions) return self::FAILURE;

        // 2. Candidats
        $depart = Candidat::withTrashed()->where('numero_inscription', 'like', 'DEMO-%')->count();
        $n = $depart;
        foreach ($sessions as $s) {
            $deja = Candidat::where('session_id', $s->id)->where('numero_inscription', 'like', 'DEMO-%')->count();
            for ($i = $deja; $i < (int) $this->option('candidats'); $i++) {
                $n++;
                $f = random_int(0, 1) === 1;
                $age = $s->examen->code === 'BEPC' ? random_int(14, 17) : random_int(20, 40);
                Candidat::create([
                    'examen_id' => $s->examen_id, 'session_id' => $s->id,
                    'nom' => self::NOMS[array_rand(self::NOMS)], 'prenoms' => ($f ? self::PRENOMS_F : self::PRENOMS_M)[array_rand($f ? self::PRENOMS_F : self::PRENOMS_M)],
                    'date_naissance' => \Carbon\Carbon::create($s->annee - $age, random_int(1, 12), random_int(1, 28))->toDateString(),
                    'lieu_naissance' => self::LIEUX[array_rand(self::LIEUX)], 'sexe' => $f ? 'F' : 'M', 'nationalite' => 'Malagasy',
                    'numero_candidat' => sprintf('DEMO-C%05d', $n), 'numero_inscription' => sprintf('DEMO-%05d', $n),
                    'centre_ecrit' => 'CEG DÉMO ' . random_int(1, 12), 'lycee_siegeant' => 'Lycée Démo ' . self::LIEUX[array_rand(self::LIEUX)],
                    'mention' => random_int(1, 4) === 1 ? ['Passable', 'Assez bien', 'Bien'][array_rand([0, 1, 2])] : null,
                ]);
            }
            if (! empty($aVerrouiller[$s->id]) && ! $s->fresh()->verrouillee) {
                $s->update(['verrouillee' => true, 'verrouillee_at' => now(), 'verrouillee_par' => $admin->id]);
            }
        }

        // 3. Diplômes sur les sessions verrouillées (≈40 % des candidats, la moitié signée)
        $nbDip = 0;
        foreach ($sessions as $s) {
            if (! $s->fresh()->verrouillee) continue;
            foreach (Candidat::with('examen', 'diplome')->where('session_id', $s->id)->where('numero_inscription', 'like', 'DEMO-%')->get() as $c) {
                if ($c->diplome || random_int(1, 100) > 40) continue;
                $d = $dipSvc->creer($c, $admin);
                if (random_int(0, 1)) $dipSvc->signer($d, $admin);
                $nbDip++;
            }
        }

        // 4. Clients fictifs
        $clients = [];
        for ($i = 1; $i <= 5; $i++) {
            $clients[] = User::firstOrCreate(['email' => "client$i@demo.test"], ['name' => "Client Démo $i", 'password' => 'Demo-Client-2026!', 'role' => 'client', 'actif' => true, 'must_change_password' => false]);
        }

        // 5. Demandes
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $nbDem = 0; $exemples = [];
        for ($i = 0; $i < (int) $this->option('demandes'); $i++) {
            $s = $sessions[array_rand($sessions)];
            $cand = Candidat::where('session_id', $s->id)->where('numero_inscription', 'like', 'DEMO-%')->inRandomOrder()->first();
            if (! $cand) continue;
            $tirage = random_int(1, 100);
            $cas = $tirage <= 60 ? 'exact' : ($tirage <= 80 ? 'faute' : 'inconnu');
            $nom = $cand->nom; $prenoms = $cand->prenoms; $dn = $cand->date_naissance; $lieu = $cand->lieu_naissance; $insc = $cand->numero_inscription;
            if ($cas === 'faute') { $nom = mb_substr($nom, 0, -1) . 'x'; }
            if ($cas === 'inconnu') { $nom = 'Inconnu' . random_int(100, 999); $insc = null; $dn = $dn->copy()->subYears(random_int(1, 3)); }

            $statuts = $cas === 'exact' ? ['recue', 'attente_pieces', 'en_verification', 'attente_correction', 'en_correction', 'attente_validation_admin', 'attente_signature', 'rejete']
                : ($cas === 'faute' ? ['recue', 'en_verification', 'attente_correction'] : ['recue', 'attente_correction', 'rejete']);
            $statut = $statuts[array_rand($statuts)];
            if ($statut === 'attente_signature' && ! $s->fresh()->verrouillee) $statut = 'en_verification';

            $complet = $statut !== 'attente_pieces';
            $recu = now()->subDays(random_int(0, 25))->setTime(random_int(8, 16), random_int(0, 59));
            $assoc = $cas === 'exact' && ! in_array($statut, ['recue', 'attente_pieces'], true);
            $numero = 'DEM' . now()->format('y') . '-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
            $code = ''; for ($k = 0; $k < 8; $k++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            $client = $clients[array_rand($clients)];

            $dem = Demande::create([
                'numero' => $numero, 'code_suivi' => Crypt::encryptString($code), 'user_id' => $client->id, 'examen_id' => $s->examen_id, 'session_id' => $s->id,
                'candidat_id' => $assoc ? $cand->id : null, 'nom' => $nom, 'prenoms' => $prenoms, 'date_naissance' => $dn, 'lieu_naissance' => $lieu,
                'numero_inscription' => $insc, 'telephone' => '03' . random_int(2, 4) . ' ' . random_int(10, 99) . ' ' . random_int(100, 999) . ' ' . random_int(10, 99),
                'statut' => $statut, 'recu_at' => $recu, 'complet_at' => $complet ? $recu : null,
                'date_prevue' => $complet ? $delai->ajouterJoursOuvres($recu)->toDateString() : null, 'service_recepteur' => config('workflow.service_recepteur'),
            ]);

            foreach ($complet ? ['cni', 'demande'] : ['cni'] as $type) {
                $chemin = "pieces/{$dem->id}/demo-$type.pdf";
                Storage::disk('local')->put($chemin, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 300 100]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n% PIECE FICTIVE");
                PieceDossier::create(['demande_id' => $dem->id, 'type' => $type, 'nom_original' => "$type-fictif.pdf", 'chemin' => $chemin, 'mime' => 'application/pdf', 'taille' => 200, 'user_id' => $client->id]);
            }
            DemandeHistorique::unguarded(function () use ($dem, $client, $recu, $complet, $statut, $admin) {
                DemandeHistorique::create(['demande_id' => $dem->id, 'user_id' => $client->id, 'de' => null, 'vers' => $complet ? 'recue' : 'attente_pieces', 'visible_client' => true, 'created_at' => $recu]);
                if (! in_array($statut, ['recue', 'attente_pieces'], true)) {
                    $motif = in_array($statut, ['attente_correction', 'rejete'], true) ? self::MOTIFS[array_rand(self::MOTIFS)] : null;
                    DemandeHistorique::create(['demande_id' => $dem->id, 'user_id' => $admin->id, 'de' => 'recue', 'vers' => $statut, 'motif' => $motif, 'visible_client' => (bool) $motif, 'created_at' => $recu->copy()->addHours(3)]);
                }
            });
            $nbDem++;
            if (count($exemples) < 5) $exemples[] = [$numero, $code, $dem->libelleStatut(), $cas];
        }

        $this->newLine();
        $this->table(['Session', 'Verrouillée', 'Candidats'], collect($sessions)->map(fn ($s) => [$s->examen->code . ' ' . $s->annee, $s->fresh()->verrouillee ? 'oui' : 'non', Candidat::where('session_id', $s->id)->where('numero_inscription', 'like', 'DEMO-%')->count()])->all());
        $this->info("$nbDip diplôme(s) créé(s), $nbDem demande(s) créée(s).");
        $this->line('Clients de test : client1@demo.test … client5@demo.test  — mot de passe : Demo-Client-2026!');
        $this->line('Exemples pour tester /suivi (numéro + code) :');
        $this->table(['N° demande', 'Code de suivi', 'Statut', 'Cas'], $exemples);
        return self::SUCCESS;
    }

    private function purger(): void
    {
        DB::transaction(function () {
            $cids = Candidat::withTrashed()->where('numero_inscription', 'like', 'DEMO-%')->pluck('id');
            $uids = User::where('email', 'like', '%@demo.test')->pluck('id');
            $dems = Demande::withTrashed()->whereIn('user_id', $uids)->pluck('id');
            $dips = Diplome::withTrashed()->whereIn('candidat_id', $cids)->pluck('id');

            foreach ($dems as $id) Storage::disk('local')->deleteDirectory("pieces/$id");
            PieceDossier::whereIn('demande_id', $dems)->delete();
            DemandeHistorique::whereIn('demande_id', $dems)->delete();
            Demande::withTrashed()->whereIn('id', $dems)->forceDelete();
            DB::table('impressions')->whereIn('diplome_id', $dips)->delete();
            DB::table('autorisations_reimpression')->whereIn('diplome_id', $dips)->delete();
            Attestation::whereIn('candidat_id', $cids)->delete();
            RegistreNotes::whereIn('candidat_id', $cids)->delete();
            Diplome::withTrashed()->whereIn('id', $dips)->forceDelete();
            Candidat::withTrashed()->whereIn('id', $cids)->forceDelete();
            NotificationInterne::whereIn('user_id', $uids)->delete();
            User::whereIn('id', $uids)->delete();
        });
    }
}