<?php
namespace App\Http\Controllers;

use App\Services\BackupService;

class SauvegardeController extends Controller
{
    public function __construct(private BackupService $svc) {}

    public function index() { return view('sauvegardes.index', ['fichiers' => $this->svc->liste()]); }

    public function store()
    {
        try { $f = $this->svc->creer(); } catch (\Throwable $e) { return back()->withErrors(['sauvegarde' => $e->getMessage()]); }
        return back()->with('success', 'Sauvegarde créée : ' . basename($f));
    }

    public function download(string $nom)
    {
        abort_unless(preg_match('/^diplomes-\d{8}-\d{6}\.sql\.gz$/', $nom), 404);   // pas de traversée de chemin
        $p = $this->svc->dossier() . '/' . $nom;
        abort_unless(is_file($p), 404);
        \App\Services\Audit::log('sauvegarde.telechargee', null, $nom);
        return response()->download($p);
    }
}
