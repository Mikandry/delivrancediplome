<?php
namespace App\Http\Controllers;

use App\Models\{Candidat, Examen, SessionExamen};
use App\Services\Audit;
use Illuminate\Http\Request;

class CandidatController extends Controller
{
    private function rules(?Candidat $c = null): array
    {
        return [
            'session_id' => 'required|exists:sessions_examens,id',
            'nom' => 'required|string|max:120',
            'prenoms' => 'required|string|max:200',
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'required|string|max:150',
            'sexe' => 'nullable|in:M,F',
            'numero_candidat' => 'nullable|string|max:40',
            'numero_inscription' => 'required|string|max:40',
            'centre_ecrit' => 'required|string|max:150',
            'lycee_siegeant' => 'required|string|max:150',
            'mention' => 'nullable|string|max:40',
        ];
    }

    public function index(Request $r)
    {
        $q = Candidat::with(['examen', 'session', 'diplome'])->latest('id');
        if ($r->filled('q')) {
            $n = Candidat::normaliser($r->q);
            $q->where(fn ($w) => $w->where('nom_normalise', 'like', "%$n%")
                ->orWhere('numero_inscription', 'like', "%{$r->q}%")->orWhere('numero_candidat', 'like', "%{$r->q}%"));
        }
        if ($r->filled('examen_id')) $q->where('examen_id', $r->examen_id);
        if ($r->filled('session_id')) $q->where('session_id', $r->session_id);
        return view('candidats.index', ['candidats' => $q->paginate(20)->withQueryString(), 'examens' => Examen::all(), 'sessions' => SessionExamen::with('examen')->get()]);
    }

    public function create() { return view('candidats.form', ['c' => new Candidat, 'sessions' => $this->sessionsOuvertes()]); }

    public function store(Request $r)
    {
        $d = $r->validate($this->rules());
        $s = SessionExamen::findOrFail($d['session_id']);
        abort_if($s->verrouillee, 403, 'Session verrouillée : ajout interdit.');
        $d['examen_id'] = $s->examen_id;
        if (Candidat::where('session_id', $s->id)->where('nom_normalise', Candidat::normaliser($d['nom'] . ' ' . $d['prenoms']))
            ->whereDate('date_naissance', $d['date_naissance'])->exists() && ! $r->boolean('confirmer_doublon')) {
            return back()->withInput()->withErrors(['nom' => 'Un candidat très ressemblant existe déjà (même nom et date de naissance). Cochez la case de confirmation si c\'est bien une personne différente.'])->with('doublon', true);
        }
        $c = Candidat::create($d);
        Audit::log('candidat.cree', $c, null, null, $d);
        return redirect()->route('candidats.index')->with('success', 'Candidat enregistré.');
    }

    public function edit(Candidat $candidat)
    {
        return view('candidats.form', ['c' => $candidat->load('session'), 'sessions' => SessionExamen::with('examen')->get()]);
    }

    public function update(Request $r, Candidat $candidat)
    {
        $candidat->load('session', 'diplome');
        if ($candidat->estVerrouille() && ! $r->user()->hasPermission('candidats.edit_locked')) {
            abort(403, 'Données verrouillées : une autorisation de l\'administrateur est requise.');
        }
        $d = $r->validate($this->rules($candidat) + ['motif' => 'required|string|min:5|max:500']);
        $motif = $d['motif']; unset($d['motif']);
        $d['examen_id'] = SessionExamen::findOrFail($d['session_id'])->examen_id;

        $candidat->fill($d);
        $diff = $candidat->getDirty();
        if (! $diff) return back()->with('warning', 'Aucune modification.');
        $anciennes = array_intersect_key($candidat->getOriginal(), $diff);
        $candidat->save();
        Audit::log('candidat.corrige', $candidat, $motif, $anciennes, $diff);
        return redirect()->route('candidats.index')->with('success', 'Correction enregistrée et journalisée.');
    }

    private function sessionsOuvertes() { return SessionExamen::with('examen')->where('verrouillee', false)->get(); }
}
