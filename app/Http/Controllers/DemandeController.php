<?php
namespace App\Http\Controllers;

use App\Models\{Candidat, Demande, DemandeHistorique, Examen, PieceDossier, SessionExamen};
use App\Services\{Audit, DelaiService, Qr, WorkflowService};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class DemandeController extends Controller
{
    public function __construct(private WorkflowService $wf) {}

    private function estClient(Request $r): bool { return $r->user()->role === 'client'; }

    /** Un client ne voit que ses demandes ; le personnel voit tout selon la permission demandes.view. */
    private function autoriser(Request $r, Demande $d): void
    {
        $u = $r->user();
        abort_unless(($this->estClient($r) && $d->user_id === $u->id) || (! $this->estClient($r) && $u->hasPermission('demandes.view')), 403);
    }

    public function index(Request $r)
    {
        $u = $r->user();
        abort_unless($u->hasPermission('demandes.own') || $u->hasPermission('demandes.view'), 403);
        $q = Demande::with('examen', 'session')->latest('id');
        if ($this->estClient($r)) {
            $q->where('user_id', $u->id);
        } else {
            if ($r->filled('statut')) $q->where('statut', $r->statut);
            if ($r->filled('examen_id')) $q->where('examen_id', $r->examen_id);
            if ($r->boolean('retard')) $q->whereDate('date_prevue', '<', today())->whereNotIn('statut', array_merge(config('workflow.termines'), ['imprime', 'disponible_retrait']));
            if ($r->filled('q')) $q->where(fn ($w) => $w->where('numero', 'like', '%' . strtoupper($r->q) . '%')->orWhere('nom', 'like', "%{$r->q}%")->orWhere('prenoms', 'like', "%{$r->q}%"));
        }
        return view('demandes.index', ['demandes' => $q->paginate(20)->withQueryString(), 'examens' => Examen::all(), 'client' => $this->estClient($r)]);
    }

    public function create(Request $r)
    {
        abort_unless($r->user()->hasPermission('demandes.own'), 403);
        return view('demandes.create', ['sessions' => SessionExamen::with('examen')->orderByDesc('annee')->get()]);
    }

    public function store(Request $r, DelaiService $delai)
    {
        abort_unless($r->user()->hasPermission('demandes.own'), 403);
        $rules = [
            'session_id' => 'required|exists:sessions_examens,id', 'nom' => 'required|string|max:120', 'prenoms' => 'required|string|max:200',
            'date_naissance' => 'required|date|before:today', 'lieu_naissance' => 'required|string|max:150',
            'numero_inscription' => 'nullable|string|max:40', 'telephone' => 'nullable|string|max:30',
        ];
        foreach (config('workflow.pieces') as $type => $def) {
            $rules["piece_$type"] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }
        $d = $r->validate($rules);
        $session = SessionExamen::findOrFail($d['session_id']);

        $demande = null;
        for ($i = 0; $i < 5 && ! $demande; $i++) {
            $numero = 'DEM' . now()->format('y') . '-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
            if (Demande::withTrashed()->where('numero', $numero)->exists()) continue;
            $code = strtoupper(substr(strtr(base64_encode(random_bytes(8)), ['+' => 'A', '/' => 'B', '=' => '', 'O' => 'C', '0' => 'D', 'I' => 'E', 'l' => 'F', '1' => 'G']), 0, 8));
            $demande = Demande::create([
                'numero' => $numero, 'code_suivi' => Crypt::encryptString($code), 'user_id' => $r->user()->id, 'examen_id' => $session->examen_id, 'session_id' => $session->id,
                'nom' => $d['nom'], 'prenoms' => $d['prenoms'], 'date_naissance' => $d['date_naissance'], 'lieu_naissance' => $d['lieu_naissance'],
                'numero_inscription' => $d['numero_inscription'] ?? null, 'telephone' => $d['telephone'] ?? null,
                'statut' => 'attente_pieces', 'recu_at' => now(), 'service_recepteur' => config('workflow.service_recepteur'),
            ]);
        }
        abort_unless($demande, 500, 'Impossible de générer un numéro de demande.');

        foreach (array_keys(config('workflow.pieces')) as $type) {
            if ($f = $r->file("piece_$type")) $this->enregistrerPiece($demande, $type, $f, $r->user()->id);
        }
        DemandeHistorique::create(['demande_id' => $demande->id, 'user_id' => $r->user()->id, 'de' => null, 'vers' => 'attente_pieces', 'visible_client' => true]);
        Audit::log('demande.recue', $demande);
        $this->wf->pieceAjoutee($demande);   // si complet : passe à « reçue » et démarre le délai
        return redirect()->route('demandes.show', $demande)->with('success', "Demande enregistrée : n° {$demande->numero}. Conservez votre code de suivi : {$demande->codeSuiviClair()}");
    }

    private function enregistrerPiece(Demande $d, string $type, $fichier, int $userId): void
    {
        $chemin = $fichier->storeAs("pieces/{$d->id}", bin2hex(random_bytes(12)) . '.' . strtolower($fichier->getClientOriginalExtension()), 'local');
        PieceDossier::create(['demande_id' => $d->id, 'type' => $type, 'nom_original' => $fichier->getClientOriginalName(), 'chemin' => $chemin,
            'mime' => $fichier->getMimeType(), 'taille' => $fichier->getSize(), 'user_id' => $userId]);
    }

    public function ajouterPiece(Request $r, Demande $demande)
    {
        $this->autoriser($r, $demande);
        abort_unless($this->estClient($r) && in_array($demande->statut, ['attente_pieces', 'recue']), 403, 'Dépôt de pièce non permis à ce stade.');
        $d = $r->validate(['type' => 'required|in:' . implode(',', array_keys(config('workflow.pieces'))), 'fichier' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $this->enregistrerPiece($demande, $d['type'], $r->file('fichier'), $r->user()->id);
        $this->wf->pieceAjoutee($demande);
        return back()->with('success', 'Pièce ajoutée.');
    }

    public function piece(Request $r, PieceDossier $piece)
    {
        $this->autoriser($r, $piece->demande);
        abort_unless(Storage::disk('local')->exists($piece->chemin), 404);
        return Storage::disk('local')->download($piece->chemin, $piece->nom_original);
    }

    public function show(Request $r, Demande $demande)
    {
        $this->autoriser($r, $demande);
        $demande->load('examen', 'session', 'candidat.session', 'diplome', 'pieces', 'historique.user');
        $u = $r->user();
        $correspondances = collect();
        if (! $this->estClient($r) && ! $demande->candidat_id) {
            $correspondances = Candidat::where('session_id', $demande->session_id)->where(fn ($w) => $w
                ->where(fn ($x) => $x->where('nom_normalise', Candidat::normaliser($demande->nom . ' ' . $demande->prenoms))->whereDate('date_naissance', $demande->date_naissance))
                ->when($demande->numero_inscription, fn ($x) => $x->orWhere('numero_inscription', $demande->numero_inscription)))->limit(10)->get();
        }
        return view('demandes.show', [
            'd' => $demande, 'client' => $this->estClient($r), 'transitions' => $this->estClient($r) ? [] : $this->wf->disponibles($demande, $u),
            'correspondances' => $correspondances, 'manquantes' => $demande->piecesManquantes(),
        ]);
    }

    public function associer(Request $r, Demande $demande)
    {
        abort_unless($r->user()->hasPermission('demandes.verifier') || $r->user()->hasPermission('demandes.corriger'), 403);
        abort_if($demande->estTerminee() || in_array($demande->statut, ['valide_impression', 'imprime', 'disponible_retrait']), 422, 'Dossier clos : association impossible.');
        $id = $r->validate(['candidat_id' => 'required|exists:candidats,id'])['candidat_id'];
        $c = Candidat::findOrFail($id);
        abort_unless($c->session_id === $demande->session_id, 422, "Ce candidat n'appartient pas à la session demandée.");
        $demande->update(['candidat_id' => $c->id]);
        DemandeHistorique::create(['demande_id' => $demande->id, 'user_id' => $r->user()->id, 'de' => $demande->statut, 'vers' => $demande->statut, 'motif' => "Association au candidat n°{$c->numero_inscription} ({$c->nomComplet()})"]);
        Audit::log('demande.associee', $demande, null, null, ['candidat_id' => $c->id]);
        return back()->with('success', 'Candidat associé.');
    }

    public function transition(Request $r, Demande $demande)
    {
        $d = $r->validate(['vers' => 'required|string', 'motif' => 'nullable|string|max:1000', 'visible_client' => 'nullable|boolean']);
        $this->wf->transition($demande, $d['vers'], $r->user(), $d['motif'] ?? null, (bool) ($d['visible_client'] ?? false));
        return back()->with('success', 'Étape enregistrée.');
    }

    public function quittance(Request $r, Demande $demande)
    {
        $this->autoriser($r, $demande);
        $demande->load('examen', 'session', 'pieces');
        $url = route('suivi', ['numero' => $demande->numero]);
        $qr = Qr::dataUri($url, 200);
        $pdf = Pdf::loadView('pdf.quittance', ['d' => $demande, 'qr' => $qr, 'url' => $url, 'delaiJours' => config('workflow.delai_jours_ouvres'),
            'pieces' => config('workflow.pieces')])->setPaper('a4')->output();
        Audit::log('quittance.generee', $demande);
        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="quittance-' . $demande->numero . '.pdf"']);
    }
}
