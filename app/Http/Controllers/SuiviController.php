<?php
namespace App\Http\Controllers;

use App\Models\Demande;
use Illuminate\Http\Request;

class SuiviController extends Controller
{
    public function form(Request $r) { return view('public.suivi', ['demande' => null, 'numero' => $r->query('numero')]); }

    public function consulter(Request $r)
    {
        $d = $r->validate(['numero' => 'required|string|max:20', 'code' => 'required|string|max:20']);
        $dem = Demande::where('numero', strtoupper(trim($d['numero'])))->first();
        $ok = $dem && hash_equals($dem->codeSuiviClair(), strtoupper(trim($d['code'])));
        // Même message dans les deux cas d'échec : on ne révèle pas l'existence d'un numéro.
        return view('public.suivi', ['demande' => $ok ? $dem : null, 'numero' => $d['numero'], 'echec' => ! $ok]);
    }
}
