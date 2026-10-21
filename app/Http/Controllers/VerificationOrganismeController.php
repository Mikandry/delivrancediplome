<?php
namespace App\Http\Controllers;

use App\Services\VerificationOrganismeService;
use Illuminate\Http\Request;

class VerificationOrganismeController extends Controller
{
    public function __construct(private VerificationOrganismeService $svc) {}

    public function form() { return view('public.organisme', ['resultats' => null]); }

    public function verifier(Request $r)
    {
        $r->validate(['cle' => 'required|string|max:100', 'fichier' => 'nullable|file|mimes:csv,txt|max:1024']);
        $org = $this->svc->authentifier($r->cle, $r->ip());
        if (! $org) return back()->withErrors(['cle' => "Clé invalide, désactivée, ou trop de tentatives (réessayez dans 15 minutes)."]);

        $lignes = [];
        if ($f = $r->file('fichier')) {
            $h = fopen($f->getRealPath(), 'r');
            $premiere = fgets($h); rewind($h);
            $sep = substr_count((string) $premiere, ';') >= substr_count((string) $premiere, ',') ? ';' : ',';
            $entete = array_map(fn ($x) => \Illuminate\Support\Str::slug(trim((string) $x), '_'), fgetcsv($h, 0, $sep) ?: []);
            while (($row = fgetcsv($h, 0, $sep)) !== false && count($lignes) < 200) {
                if (! array_filter($row)) continue;
                $q = [];
                foreach ($entete as $i => $k) $q[$k] = $row[$i] ?? '';
                $q['date_naissance'] = $this->date($q['date_naissance'] ?? '');
                $lignes[] = $q;
            }
            fclose($h);
        } else {
            $q = $r->only(['nom', 'prenoms', 'date_naissance', 'numero_inscription', 'examen', 'annee']);
            $q['date_naissance'] = $this->date($q['date_naissance'] ?? '');
            $lignes[] = $q;
        }

        $resultats = [];
        foreach ($lignes as $q) {
            $res = $this->svc->verifier($org, $q);
            if ($res === null) { $resultats[] = ['q' => $q, 'res' => ['statut' => 'limite', 'libelle' => 'Limite horaire atteinte : réessayez plus tard.', 'details' => []]]; break; }
            $resultats[] = ['q' => $q, 'res' => $res];
        }
        $this->svc->journaliser($org, count($resultats));
        return view('public.organisme', ['resultats' => $resultats, 'organisme' => $org]);
    }

    private function date(string $v): string
    {
        $v = trim($v);
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $f) { $d = \DateTime::createFromFormat('!' . $f, $v); if ($d && $d->format($f) === $v) return $d->format('Y-m-d'); }
        return '';
    }
}
