<?php
namespace App\Http\Controllers;

use App\Models\Organisme;
use App\Services\Audit;
use Illuminate\Http\Request;

class OrganismeController extends Controller
{
    public function index() { return view('organismes.index', ['organismes' => Organisme::orderBy('nom')->get()]); }

    public function store(Request $r)
    {
        $d = $r->validate(['nom' => 'required|string|max:200', 'email' => 'nullable|email', 'limite_heure' => 'required|integer|min:1|max:5000']);
        $cle = 'org_' . bin2hex(random_bytes(20));
        $o = Organisme::create($d + ['cle_hash' => hash('sha256', $cle), 'cle_prefixe' => substr($cle, 0, 8), 'actif' => true]);
        Audit::log('organisme.cree', $o, null, null, ['nom' => $o->nom]);
        return back()->with('cle_generee', $cle)->with('success', "Organisme créé. Copiez la clé maintenant : elle ne sera plus jamais affichée.");
    }

    public function toggle(Organisme $organisme)
    {
        $organisme->update(['actif' => ! $organisme->actif]);
        Audit::log($organisme->actif ? 'organisme.reactive' : 'organisme.desactive', $organisme);
        return back()->with('success', 'État modifié.');
    }
}
