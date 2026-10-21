<?php
namespace App\Http\Controllers;

use App\Models\{Candidat, RegistreNotes};
use App\Services\{Audit, Qr};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistreController extends Controller
{
    private function regles(): array
    {
        $max = config('registres.note_max');
        return [
            'reference_demande' => 'nullable|string|max:255',
            'resultat_officiel' => 'nullable|in:' . implode(',', array_keys(config('registres.resultats'))),
            'total_saisi' => 'nullable|string|max:20', 'moyenne_saisie' => 'nullable|string|max:20', 'observations' => 'nullable|string|max:1000',
            'matieres' => 'required|array|min:1|max:40', 'matieres.*' => 'required|string|max:120',
            'notes' => 'array', 'notes.*' => "nullable|numeric|min:0|max:$max",
            'coefs' => 'array', 'coefs.*' => 'nullable|numeric|min:0|max:99',
        ];
    }

    public function index(Request $r)
    {
        $cands = collect();
        if ($r->filled('q')) {
            $n = Candidat::normaliser($r->q);
            $cands = Candidat::with('examen', 'session')->where(fn ($w) => $w->where('nom_normalise', 'like', "%$n%")->orWhere('numero_inscription', 'like', "%{$r->q}%")->orWhere('numero_candidat', 'like', "%{$r->q}%"))->limit(15)->get();
        }
        return view('registres.index', ['registres' => RegistreNotes::with('candidat.examen', 'candidat.session')->latest('id')->paginate(20), 'cands' => $cands]);
    }

    public function create(Candidat $candidat) { return view('registres.form', ['c' => $candidat->load('examen', 'session'), 'reg' => new RegistreNotes]); }

    public function store(Request $r, Candidat $candidat)
    {
        $d = $r->validate($this->regles());
        $reg = DB::transaction(function () use ($d, $candidat, $r) {
            $reg = RegistreNotes::create(['candidat_id' => $candidat->id, 'reference_demande' => $d['reference_demande'] ?? null, 'resultat_officiel' => $d['resultat_officiel'] ?? null,
                'total_saisi' => $d['total_saisi'] ?? null, 'moyenne_saisie' => $d['moyenne_saisie'] ?? null, 'observations' => $d['observations'] ?? null,
                'token_verification' => bin2hex(random_bytes(24)), 'saisi_par' => $r->user()->id, 'date_etablissement' => today()]);
            $this->enregistrerLignes($reg, $d);
            Audit::log('registre.cree', $reg, null, null, ['lignes' => $reg->lignes()->get(['matiere', 'coefficient', 'note'])->toArray()]);
            return $reg;
        });
        return redirect()->route('registres.show', $reg)->with('success', 'Registre enregistré.');
    }

    public function show(RegistreNotes $registre) { return view('registres.show', ['reg' => $registre->load('candidat.examen', 'candidat.session', 'lignes', 'saisisseur')]); }

    public function edit(RegistreNotes $registre) { return view('registres.form', ['c' => $registre->candidat->load('examen', 'session'), 'reg' => $registre->load('lignes')]); }

    public function update(Request $r, RegistreNotes $registre)
    {
        $d = $r->validate($this->regles() + ['motif' => 'required|string|min:5|max:500']);
        DB::transaction(function () use ($registre, $d) {
            $anciennes = ['champs' => $registre->only(['resultat_officiel', 'total_saisi', 'moyenne_saisie', 'observations']), 'lignes' => $registre->lignes()->get(['matiere', 'coefficient', 'note'])->toArray()];
            $registre->update(['reference_demande' => $d['reference_demande'] ?? null, 'resultat_officiel' => $d['resultat_officiel'] ?? null, 'total_saisi' => $d['total_saisi'] ?? null,
                'moyenne_saisie' => $d['moyenne_saisie'] ?? null, 'observations' => $d['observations'] ?? null]);
            $registre->lignes()->delete();
            $this->enregistrerLignes($registre, $d);
            Audit::log('registre.corrige', $registre, $d['motif'], $anciennes, ['champs' => $registre->only(['resultat_officiel', 'total_saisi', 'moyenne_saisie', 'observations']), 'lignes' => $registre->lignes()->get(['matiere', 'coefficient', 'note'])->toArray()]);
        });
        return redirect()->route('registres.show', $registre)->with('success', 'Correction enregistrée et journalisée.');
    }

    private function enregistrerLignes(RegistreNotes $reg, array $d): void
    {
        foreach ($d['matieres'] as $i => $m) {
            $reg->lignes()->create(['ordre' => $i, 'matiere' => $m, 'note' => ($d['notes'][$i] ?? '') === '' ? null : $d['notes'][$i], 'coefficient' => ($d['coefs'][$i] ?? '') === '' ? null : $d['coefs'][$i]]);
        }
    }

    public function pdf(RegistreNotes $registre)
    {
        $registre->load('candidat.examen', 'candidat.session', 'lignes');
        $url = url('/verifier/' . $registre->token_verification);
        Audit::log('registre.pdf', $registre);
        $pdf = Pdf::loadView('pdf.registre', ['reg' => $registre, 'c' => $registre->candidat, 'qr' => Qr::dataUri($url), 'url' => $url, 'rv' => config('registres.resultats')])->setPaper('a4')->output();
        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="registre-' . $registre->id . '.pdf"']);
    }
}
