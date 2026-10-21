<?php
namespace App\Http\Controllers;

use App\Models\{Examen, SessionExamen};
use App\Services\Audit;
use Illuminate\Http\Request;

class SessionExamenController extends Controller
{
    public function index()
    {
        return view('sessions.index', ['sessions' => SessionExamen::with('examen')->withCount('candidats')->orderByDesc('annee')->get(), 'examens' => Examen::all()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['examen_id' => 'required|exists:examens,id', 'annee' => 'required|integer|between:1990,2100', 'libelle' => 'nullable|string|max:100',
            'pv_date' => 'nullable|date', 'pv_reference' => 'nullable|string|max:100', 'arrete_reference' => 'nullable|string|max:255']);
        abort_if(SessionExamen::where('examen_id', $d['examen_id'])->where('annee', $d['annee'])->exists(), 422, 'Cette session existe déjà.');
        $s = SessionExamen::create($d);
        Audit::log('session.cree', $s, null, null, $d);
        return back()->with('success', 'Session créée.');
    }

    public function update(Request $r, SessionExamen $session)
    {
        abort_if($session->verrouillee, 403, 'Session verrouillée : déverrouillage exceptionnel requis.');
        $d = $r->validate(['libelle' => 'nullable|string|max:100', 'pv_date' => 'nullable|date', 'pv_reference' => 'nullable|string|max:100', 'arrete_reference' => 'nullable|string|max:255']);
        $session->fill($d);
        $anciennes = array_intersect_key($session->getOriginal(), $session->getDirty());
        $nouvelles = $session->getDirty();
        $session->save();
        Audit::log('session.modifiee', $session, null, $anciennes, $nouvelles);
        return back()->with('success', 'Session mise à jour.');
    }

    public function verrouiller(Request $r, SessionExamen $session)
    {
        abort_if($session->verrouillee, 422, 'Déjà verrouillée.');
        $session->update(['verrouillee' => true, 'verrouillee_at' => now(), 'verrouillee_par' => $r->user()->id]);
        Audit::log('session.verrouillee', $session);
        return back()->with('success', 'Liste validée et verrouillée.');
    }

    public function deverrouiller(Request $r, SessionExamen $session)
    {
        $d = $r->validate(['motif' => 'required|string|min:10|max:500']);
        abort_unless($session->verrouillee, 422, "Cette session n'est pas verrouillée.");
        $session->update(['verrouillee' => false, 'verrouillee_at' => null, 'verrouillee_par' => null]);
        Audit::log('session.deverrouillee', $session, $d['motif']);
        return back()->with('warning', 'Session déverrouillée à titre exceptionnel (journalisé). Pensez à la reverrouiller après correction.');
    }
}
