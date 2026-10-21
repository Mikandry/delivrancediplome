<?php
namespace App\Http\Controllers;

use App\Models\{Candidat, Demande, Diplome, Impression, AutorisationReimpression};

class DashboardController extends Controller
{
    public function index()
    {
        $par = Diplome::selectRaw('statut, count(*) n')->groupBy('statut')->pluck('n', 'statut');
        return view('dashboard', [
            'candidats' => Candidat::count(),
            'par_statut' => $par,
            'total_diplomes' => $par->sum(),
            'impressions' => Impression::count(),
            'reimpressions' => AutorisationReimpression::count(),
            'aujourdhui' => Diplome::whereDate('created_at', today())->count(),
            'dem_jour' => Demande::whereDate('recu_at', today())->count(),
            'dem_mois' => Demande::whereYear('recu_at', now()->year)->whereMonth('recu_at', now()->month)->count(),
            'dem_annee' => Demande::whereYear('recu_at', now()->year)->count(),
            'dem_statut' => Demande::selectRaw('statut, count(*) n')->groupBy('statut')->pluck('n', 'statut'),
            'dem_retard' => Demande::whereDate('date_prevue', '<', today())->whereNotIn('statut', ['delivre', 'rejete', 'archive', 'imprime', 'disponible_retrait'])->count(),
        ]);
    }
}
