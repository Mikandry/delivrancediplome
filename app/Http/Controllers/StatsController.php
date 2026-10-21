<?php
namespace App\Http\Controllers;

use App\Models\{Examen, SessionExamen};
use App\Services\StatsService;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $r, StatsService $svc)
    {
        $f = $r->validate(['du' => 'nullable|date', 'au' => 'nullable|date', 'examen_id' => 'nullable|integer', 'session_id' => 'nullable|integer', 'statut' => 'nullable|string']);
        return view('stats.index', ['s' => $svc->calculer($f), 'f' => $f, 'examens' => Examen::all(), 'sessions' => SessionExamen::with('examen')->get()]);
    }
}
