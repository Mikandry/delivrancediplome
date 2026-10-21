<?php
namespace App\Http\Controllers;

use App\Models\{Examen, Import, SessionExamen};
use App\Services\{Audit, ImportService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImportController extends Controller
{
    public function __construct(private ImportService $svc) {}

    public function index()
    {
        return view('imports.index', ['imports' => Import::with('session.examen', 'user')->latest()->paginate(15), 'sessions' => SessionExamen::with('examen')->where('verrouillee', false)->get(), 'examens' => Examen::all()]);
    }

    public function store(Request $r)
    {
        $r->validate(['session_id' => 'required|exists:sessions_examens,id', 'fichier' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        $session = SessionExamen::findOrFail($r->session_id);
        abort_if($session->verrouillee, 403, 'Session verrouillée : importation impossible.');

        $f = $r->file('fichier');
        $chemin = $f->storeAs('imports', bin2hex(random_bytes(12)) . '.' . strtolower($f->getClientOriginalExtension()), 'local');
        $imp = Import::create(['session_id' => $session->id, 'user_id' => $r->user()->id, 'fichier_original' => $f->getClientOriginalName(), 'chemin' => $chemin]);
        try {
            $this->svc->analyser($imp, Storage::disk('local')->path($chemin));
        } catch (RuntimeException $e) {
            $imp->update(['statut' => 'annule']);
            return back()->withErrors(['fichier' => $e->getMessage()]);
        }
        Audit::log('import.analyse', $imp, null, null, ['fichier' => $imp->fichier_original, 'lignes' => $imp->total]);
        return redirect()->route('imports.show', $imp);
    }

    public function show(Request $r, Import $import)
    {
        $q = $import->lignes()->orderBy('ligne');
        match ($r->get('filtre')) {
            'erreurs' => $q->whereNotNull('erreurs'),
            'doublons' => $q->whereNotNull('doublon'),
            'ok' => $q->whereNull('erreurs')->whereNull('doublon'),
            default => null,
        };
        $stats = [
            'ok' => $import->lignes()->whereNull('erreurs')->whereNull('doublon')->count(),
            'erreurs' => $import->lignes()->whereNotNull('erreurs')->count(),
            'doublons' => $import->lignes()->whereNotNull('doublon')->count(),
        ];
        return view('imports.show', ['imp' => $import->load('session.examen'), 'lignes' => $q->paginate(50)->withQueryString(), 'stats' => $stats]);
    }

    public function confirmer(Request $r, Import $import)
    {
        try {
            $n = $this->svc->confirmer($import, array_map('intval', $r->input('forcer', [])), $r->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['import' => $e->getMessage()]);
        }
        return redirect()->route('imports.index')->with('success', "$n candidat(s) importé(s).");
    }

    public function annuler(Import $import)
    {
        abort_unless($import->statut === 'apercu', 422);
        $import->update(['statut' => 'annule']);
        Audit::log('import.annule', $import);
        return redirect()->route('imports.index')->with('warning', 'Import annulé : aucune donnée enregistrée.');
    }

    public function modele(string $format)
    {
        abort_unless(in_array($format, ['xlsx', 'csv']), 404);
        [$tmp, $nom] = $this->svc->modele($format);
        return response()->download($tmp, $nom)->deleteFileAfterSend(true);
    }
}
