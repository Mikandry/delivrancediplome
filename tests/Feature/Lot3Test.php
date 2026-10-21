<?php
namespace Tests\Feature;

use App\Models\{Candidat, Examen, Organisme, SessionExamen, User};
use App\Services\{AttestationService, DiplomeService, VerificationOrganismeService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Lot3Test extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User { return User::factory()->create(['role' => $role, 'actif' => true, 'must_change_password' => false]); }

    private function candidat(bool $verrouille = false): Candidat
    {
        $e = Examen::firstOrCreate(['code' => 'BEPC'], ['intitule' => 'BEPC']);
        $s = SessionExamen::firstOrCreate(['examen_id' => $e->id, 'annee' => 2026], ['verrouillee' => $verrouille]);
        return Candidat::create(['examen_id' => $e->id, 'session_id' => $s->id, 'nom' => 'Rakoto', 'prenoms' => 'Jean', 'date_naissance' => '2010-01-01',
            'lieu_naissance' => 'Toamasina', 'numero_inscription' => 'INS-1', 'centre_ecrit' => 'CEG', 'lycee_siegeant' => 'Lycée']);
    }

    public function test_attestation_de_reussite_refusee_tant_que_non_autorisee_ou_non_approuvee(): void
    {
        $c = $this->candidat(true);   // liste approuvée
        $sec = $this->user('secretaire');
        config(['attestations.reussite_autorisee' => false]);
        $this->expectException(ValidationException::class);
        app(AttestationService::class)->emettre($c, 'reussite', $sec, null);
    }

    public function test_attestation_de_reussite_refusee_si_resultat_non_approuve(): void
    {
        $c = $this->candidat(false);
        config(['attestations.reussite_autorisee' => true]);
        $this->expectException(ValidationException::class);
        app(AttestationService::class)->emettre($c, 'reussite', $this->user('secretaire'), null);
    }

    public function test_attestation_provisoire_et_remplacement(): void
    {
        $c = $this->candidat(false); $sec = $this->user('secretaire');
        $svc = app(AttestationService::class);
        $a1 = $svc->emettre($c, 'provisoire', $sec, null);
        $this->assertSame('provisoire', $a1->type);
        $this->assertNotNull($a1->valide_jusqu);
        $a2 = $svc->emettre($c, 'provisoire', $sec, null);
        $this->assertSame('remplace', $a1->fresh()->statut);
        $this->assertSame('valide', $a2->fresh()->statut);
        $this->get(route('verification', $a2->token_verification))->assertOk()->assertSee('PROVISOIRE');
    }

    public function test_attestation_de_perte_exige_un_diplome_deja_imprime(): void
    {
        $c = $this->candidat(false);
        $this->expectException(ValidationException::class);
        app(AttestationService::class)->emettre($c, 'perte', $this->user('secretaire'), 'Déclaration de perte n°1');
    }

    public function test_un_saisisseur_ne_peut_pas_emettre_d_attestation(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(AttestationService::class)->emettre($this->candidat(), 'provisoire', $this->user('saisisseur'), null);
    }

    public function test_verification_organisme_distingue_les_statuts(): void
    {
        $c = $this->candidat();
        $o = Organisme::create(['nom' => 'Ministère test', 'cle_hash' => hash('sha256', 'org_test'), 'cle_prefixe' => 'org_test', 'actif' => true, 'limite_heure' => 100]);
        $svc = app(VerificationOrganismeService::class);

        $this->assertSame('admis_present', $svc->verifier($o, ['nom' => 'Rakoto', 'prenoms' => 'Jean', 'date_naissance' => '2010-01-01'])['statut']);
        $this->assertSame('non_retrouve', $svc->verifier($o, ['nom' => 'Inconnu', 'prenoms' => 'Zéro', 'date_naissance' => '2000-01-01'])['statut']);
        $this->assertSame('donnees_insuffisantes', $svc->verifier($o, ['nom' => 'Rakoto'])['statut']);
    }

    public function test_cle_organisme_invalide_est_refusee(): void
    {
        $this->post(route('organisme.verifier'), ['cle' => 'mauvaise', 'nom' => 'X'])->assertSessionHasErrors('cle');
    }

    public function test_limite_horaire_organisme(): void
    {
        $o = Organisme::create(['nom' => 'Petit', 'cle_hash' => hash('sha256', 'k'), 'cle_prefixe' => 'k', 'actif' => true, 'limite_heure' => 2]);
        $svc = app(VerificationOrganismeService::class);
        $q = ['nom' => 'A', 'date_naissance' => '2000-01-01'];
        $this->assertNotNull($svc->verifier($o, $q)); $this->assertNotNull($svc->verifier($o, $q));
        $this->assertNull($svc->verifier($o, $q));
    }

    public function test_acces_reserves(): void
    {
        $this->actingAs($this->user('saisisseur'));
        $this->get(route('sauvegardes.index'))->assertForbidden();
        $this->get(route('stats.index'))->assertForbidden();
        $this->get(route('audit.index'))->assertForbidden();
        $this->get(route('organismes.index'))->assertForbidden();
        $this->actingAs($this->user('administrateur'))->get(route('stats.index'))->assertOk();
        $this->actingAs($this->user('super_admin'))->get(route('audit.index'))->assertOk();
    }

    public function test_journal_d_audit_non_modifiable_en_base(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'])) $this->markTestSkipped('Triggers MySQL requis.');
        \App\Services\Audit::log('test.action');
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('journaux_audit')->update(['action' => 'falsifie']);
    }
}
