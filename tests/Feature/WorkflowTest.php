<?php
namespace Tests\Feature;

use App\Models\{Candidat, Demande, Examen, Import, JourFerie, PieceDossier, SessionExamen, User};
use App\Services\{DelaiService, DiplomeService, ImportService, WorkflowService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User { return User::factory()->create(['role' => $role, 'actif' => true, 'must_change_password' => false]); }

    private function dossier(?User $client = null, bool $verrouille = false): Demande
    {
        $client ??= $this->user('client');
        $e = Examen::firstOrCreate(['code' => 'BEPC'], ['intitule' => 'BEPC']);
        $s = SessionExamen::firstOrCreate(['examen_id' => $e->id, 'annee' => 2026], ['verrouillee' => $verrouille]);
        $c = Candidat::create(['examen_id' => $e->id, 'session_id' => $s->id, 'nom' => 'Demo', 'prenoms' => 'Test', 'date_naissance' => '2010-01-01',
            'lieu_naissance' => 'Toamasina', 'numero_inscription' => 'T' . random_int(1000, 9999), 'centre_ecrit' => 'CEG', 'lycee_siegeant' => 'Lycée']);
        $d = Demande::create(['numero' => 'DEM26-' . strtoupper(bin2hex(random_bytes(4))), 'code_suivi' => Crypt::encryptString('ABCD2345'), 'user_id' => $client->id,
            'examen_id' => $e->id, 'session_id' => $s->id, 'candidat_id' => $c->id, 'nom' => 'Demo', 'prenoms' => 'Test', 'date_naissance' => '2010-01-01',
            'lieu_naissance' => 'Toamasina', 'statut' => 'recue', 'recu_at' => now(), 'service_recepteur' => 'Guichet']);
        foreach (['cni', 'demande'] as $t) PieceDossier::create(['demande_id' => $d->id, 'type' => $t, 'nom_original' => "$t.pdf", 'chemin' => "x/$t.pdf", 'user_id' => $client->id]);
        return $d;
    }

    public function test_un_client_ne_voit_pas_la_demande_d_un_autre(): void
    {
        $d = $this->dossier($this->user('client'));
        $this->actingAs($this->user('client'))->get(route('demandes.show', $d))->assertForbidden();
        $this->actingAs($d->user)->get(route('demandes.show', $d))->assertOk();
    }

    public function test_transitions_incoherentes_et_droits(): void
    {
        $d = $this->dossier();
        $wf = app(WorkflowService::class);
        try { $wf->transition($d, 'delivre', $this->user('agent_validation')); $this->fail('Transition incohérente acceptée'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('statut', $e->errors()); }

        try { $wf->transition($d, 'en_verification', $this->user('saisisseur')); $this->fail('Rôle non autorisé accepté'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }

        try { $wf->transition($d, 'attente_pieces', $this->user('agent_validation'), ''); $this->fail('Motif obligatoire ignoré'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('motif', $e->errors()); }
    }

    public function test_le_delai_exclut_week_ends_et_jours_feries(): void
    {
        $lundi = Carbon::parse('2026-10-19');
        $this->assertSame('2026-10-26', app(DelaiService::class)->ajouterJoursOuvres($lundi, 5)->toDateString());
        JourFerie::create(['jour' => '2026-10-22', 'libelle' => 'Test']);
        $this->assertSame('2026-10-27', app(DelaiService::class)->ajouterJoursOuvres($lundi, 5)->toDateString());
    }

    public function test_circuit_complet_jusqu_a_l_impression(): void
    {
        $d = $this->dossier();
        $wf = app(WorkflowService::class);
        $agent = $this->user('agent_validation'); $saisi = $this->user('saisisseur'); $admin = $this->user('administrateur');
        $sec = $this->user('secretaire'); $imp = $this->user('agent_impression');

        $wf->transition($d, 'en_verification', $agent);
        // Liste non verrouillée : la transmission au secrétaire est refusée
        try { $wf->transition($d->fresh(), 'attente_signature', $agent); $this->fail('Liste non verrouillée acceptée'); } catch (ValidationException $e) { $this->assertTrue(true); }

        $wf->transition($d->fresh(), 'attente_correction', $agent, 'Lieu de naissance à corriger', true);
        $wf->transition($d->fresh(), 'en_correction', $saisi);
        $wf->transition($d->fresh(), 'attente_validation_admin', $saisi);
        $wf->transition($d->fresh(), 'en_verification', $admin);           // reverrouille la session
        $this->assertTrue($d->session->fresh()->verrouillee);
        $wf->transition($d->fresh(), 'attente_signature', $agent);
        $wf->transition($d->fresh(), 'valide_impression', $sec);

        $d = $d->fresh();
        $this->assertNotNull($d->diplome_id);
        $this->assertNotNull($d->diplome->date_signature);

        app(DiplomeService::class)->imprimer($d->diplome, $imp);
        $this->assertSame('imprime', $d->fresh()->statut);
        $this->assertGreaterThanOrEqual(8, $d->historique()->count());
    }

    public function test_import_detecte_erreurs_et_doublons(): void
    {
        $e = Examen::create(['code' => 'BEPC', 'intitule' => 'BEPC']);
        $s = SessionExamen::create(['examen_id' => $e->id, 'annee' => 2026]);
        Candidat::create(['examen_id' => $e->id, 'session_id' => $s->id, 'nom' => 'Existant', 'prenoms' => 'Un', 'date_naissance' => '2010-05-05',
            'lieu_naissance' => 'X', 'numero_inscription' => 'INS-1', 'centre_ecrit' => 'C', 'lycee_siegeant' => 'L']);

        $csv = "Nom;Prénoms;Date de naissance;Lieu de naissance;Sexe;N° candidat;N° inscription;Centre d'écrit;Lycée siégeant;Mention\n"
            . "Valide;Deux;02/12/2010;Toamasina;M;;INS-2;CEG;Lycée;\n"
            . "Mauvaise;Date;31/02/2010;Toamasina;F;;INS-3;CEG;Lycée;\n"
            . "Doublon;Exact;01/01/2010;Toamasina;M;;INS-1;CEG;Lycée;\n"
            . "Existant;Un;05/05/2010;Autre lieu;M;;INS-9;CEG;Lycée;\n";
        $f = tempnam(sys_get_temp_dir(), 'imp') . '.csv';
        file_put_contents($f, $csv);

        $imp = Import::create(['session_id' => $s->id, 'user_id' => $this->user('saisisseur')->id, 'fichier_original' => 'test.csv', 'chemin' => $f]);
        app(ImportService::class)->analyser($imp, $f);

        $l = $imp->lignes()->orderBy('ligne')->get();
        $this->assertCount(4, $l);
        $this->assertNull($l[0]->erreurs); $this->assertNull($l[0]->doublon);
        $this->assertNotNull($l[1]->erreurs);
        $this->assertSame('exact', $l[2]->doublon);
        $this->assertSame('ressemblance', $l[3]->doublon);

        $n = app(ImportService::class)->confirmer($imp, []);
        $this->assertSame(1, $n);                       // seule la ligne valide est importée
        $this->assertSame(2, Candidat::count());         // la ressemblance n'est jamais fusionnée ni importée sans confirmation
        @unlink($f);
    }
}
