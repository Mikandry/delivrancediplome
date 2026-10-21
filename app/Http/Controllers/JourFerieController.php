<?php
namespace App\Http\Controllers;

use App\Models\JourFerie;
use App\Services\Audit;
use Illuminate\Http\Request;

class JourFerieController extends Controller
{
    public function index() { return view('feries.index', ['feries' => JourFerie::orderBy('jour')->get()]); }

    public function store(Request $r)
    {
        $d = $r->validate(['jour' => 'required|date|unique:jours_feries,jour', 'libelle' => 'required|string|max:120']);
        Audit::log('ferie.ajoute', JourFerie::create($d), null, null, $d);
        return back()->with('success', 'Jour férié ajouté.');
    }

    public function destroy(JourFerie $ferie)
    {
        Audit::log('ferie.supprime', $ferie, null, $ferie->toArray());
        $ferie->delete();
        return back()->with('success', 'Jour férié supprimé.');
    }
}
