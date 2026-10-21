<?php
namespace App\Http\Controllers;

use App\Models\{Attestation, Candidat};
use App\Services\AttestationService;
use Illuminate\Http\Request;

class AttestationController extends Controller
{
    public function __construct(private AttestationService $svc) {}

    public function index(Request $r)
    {
        $cands = collect();
        if ($r->filled('q')) {
            $n = Candidat::normaliser($r->q);
            $cands = Candidat::with('examen', 'session')->where(fn ($w) => $w->where('nom_normalise', 'like', "%$n%")->orWhere('numero_inscription', 'like', "%{$r->q}%"))->limit(15)->get();
        }
        return view('attestations.index', ['attestations' => Attestation::with('candidat.examen', 'candidat.session')->latest('id')->paginate(20), 'cands' => $cands]);
    }

    public function create(Candidat $candidat)
    {
        $candidat->load('examen', 'session', 'diplome');
        return view('attestations.create', ['c' => $candidat, 'elig' => $this->svc->eligibilite($candidat)]);
    }

    public function store(Request $r, Candidat $candidat)
    {
        $d = $r->validate(['type' => 'required|string', 'reference' => 'nullable|string|max:1000']);
        $a = $this->svc->emettre($candidat->load('session', 'diplome', 'examen'), $d['type'], $r->user(), $d['reference'] ?? null);
        return redirect()->route('attestations.index')->with('success', "Attestation {$a->numero} émise.");
    }

    public function pdf(Attestation $attestation)
    {
        return response($this->svc->pdf($attestation), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $attestation->numero . '.pdf"']);
    }

    public function revoquer(Request $r, Attestation $attestation)
    {
        $d = $r->validate(['motif' => 'required|string|min:5|max:500']);
        $this->svc->revoquer($attestation, $r->user(), $d['motif']);
        return back()->with('success', 'Attestation révoquée.');
    }
}
