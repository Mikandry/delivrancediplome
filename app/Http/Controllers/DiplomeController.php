<?php
namespace App\Http\Controllers;

use App\Models\{Candidat, Diplome, Examen};
use App\Services\DiplomeService;
use Illuminate\Http\Request;

class DiplomeController extends Controller
{
    public function __construct(private DiplomeService $svc) {}

    public function index(Request $r)
    {
        $q = Diplome::with('candidat.examen', 'candidat.session')->latest('id');
        if ($r->filled('statut')) $q->where('statut', $r->statut);
        if ($r->filled('examen_id')) $q->whereHas('candidat', fn ($w) => $w->where('examen_id', $r->examen_id));
        if ($r->filled('q')) $q->where(fn ($w) => $w->where('numero_diplome', 'like', "%{$r->q}%")
            ->orWhereHas('candidat', fn ($c) => $c->where('nom_normalise', 'like', '%' . Candidat::normaliser($r->q) . '%')));
        return view('diplomes.index', ['diplomes' => $q->paginate(20)->withQueryString(), 'examens' => Examen::all()]);
    }

    public function store(Request $r)
    {
        $c = Candidat::with('examen', 'session')->findOrFail($r->validate(['candidat_id' => 'required|exists:candidats,id'])['candidat_id']);
        $d = $this->svc->creer($c, $r->user());
        return redirect()->route('diplomes.show', $d)->with('success', 'Diplôme créé en brouillon.');
    }

    public function show(Diplome $diplome)
    {
        return view('diplomes.show', ['d' => $diplome->load('candidat.examen', 'candidat.session', 'impressions.user', 'autorisations.autorisateur')]);
    }

    public function apercu(Diplome $diplome)
    {
        return response($this->svc->apercu($diplome), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="apercu.pdf"']);
    }

    public function signer(Request $r, Diplome $diplome)
    {
        $this->svc->signer($diplome, $r->user());
        return back()->with('success', 'Diplôme validé. Date de signature enregistrée automatiquement.');
    }

    public function imprimer(Request $r, Diplome $diplome)
    {
        $pdf = $this->svc->imprimer($diplome, $r->user());
        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="diplome-' . $diplome->id . '.pdf"']);
    }

    public function autoriserReimpression(Request $r, Diplome $diplome)
    {
        $d = $r->validate(['motif' => 'required|string|min:10|max:500']);
        $this->svc->autoriserReimpression($diplome, $r->user(), $d['motif']);
        return back()->with('success', 'Réimpression autorisée (usage unique).');
    }
}
