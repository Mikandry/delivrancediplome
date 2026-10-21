<?php
namespace Tests\Feature;

use App\Models\{Candidat, Examen, SessionExamen, User};
use App\Services\DiplomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DiplomeSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function candidat(bool $verrouille = false): Candidat
    {
        $e = Examen::create(['code' => 'BEPC', 'intitule' => 'BEPC']);
        $s = SessionExamen::create(['examen_id' => $e->id, 'annee' => 2026, 'verrouillee' => $verrouille]);
        return Candidat::create(['examen_id' => $e->id, 'session_id' => $s->id, 'nom' => 'Demo', 'prenoms' => 'Test', 'date_naissance' => '2010-01-01',
            'lieu_naissance' => 'Toamasina', 'numero_inscription' => 'T1', 'centre_ecrit' => 'CEG', 'lycee_siegeant' => 'Lycée']);
    }
    private function user(string $role): User { return User::factory()->create(['role' => $role, 'actif' => true, 'must_change_password' => false]); }

    public function test_saisisseur_ne_modifie_pas_une_liste_verrouillee(): void
    {
        $c = $this->candidat(true);
        $this->actingAs($this->user('saisisseur'))->put(route('candidats.update', $c), [
            'session_id' => $c->session_id, 'nom' => 'Autre', 'prenoms' => 'X', 'date_naissance' => '2010-01-01', 'lieu_naissance' => 'Y',
            'numero_inscription' => 'T1', 'centre_ecrit' => 'C', 'lycee_siegeant' => 'L', 'motif' => 'tentative'])->assertForbidden();
        $this->assertSame('Demo', $c->fresh()->nom);
    }

    public function test_secretaire_ne_modifie_pas_les_donnees_de_base(): void
    {
        $c = $this->candidat();
        $this->actingAs($this->user('secretaire'))->put(route('candidats.update', $c), ['nom' => 'Hack'])->assertForbidden();
    }

    public function test_impression_unique_puis_reimpression_autorisee_une_seule_fois(): void
    {
        $c = $this->candidat();
        $svc = app(DiplomeService::class);
        $sa = $this->user('super_admin'); $imp = $this->user('agent_impression'); $sec = $this->user('secretaire');
        $this->actingAs($sa);
        $d = $svc->creer($c->load('examen'), $sa);
        $svc->signer($d, $sec);
        $this->assertNotNull($d->fresh()->date_signature);

        $this->actingAs($imp);
        $svc->imprimer($d, $imp);
        $this->assertSame('imprime', $d->fresh()->statut);

        try { $svc->imprimer($d->fresh(), $imp); $this->fail('Seconde impression non bloquée'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }

        try { $svc->autoriserReimpression($d->fresh(), $imp, 'motif de test'); $this->fail('Non super admin accepté'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }

        $this->actingAs($sa);
        $svc->autoriserReimpression($d->fresh(), $sa, 'Diplôme détérioré à la remise');
        $this->actingAs($imp);
        $svc->imprimer($d->fresh(), $imp);
        $this->assertSame(2, $d->fresh()->nb_impressions);
        $this->expectException(HttpException::class);
        $svc->imprimer($d->fresh(), $imp);
    }

    public function test_page_publique_ne_montre_pas_un_brouillon(): void
    {
        $c = $this->candidat(); $u = $this->user('saisisseur');
        $this->actingAs($u);
        $d = app(DiplomeService::class)->creer($c->load('examen'), $u);
        $this->get(route('verification', $d->token_verification))->assertSee('Document non reconnu');
    }
}
