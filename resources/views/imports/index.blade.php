@extends('layouts.app')
@section('titre', 'Import des listes des admis')
@section('contenu')
<div class="card card-body mb-4"><h6>Nouvel import (Excel .xlsx/.xls ou CSV)</h6>
<p class="small text-muted mb-2">Téléchargez le modèle : <a href="{{ route('imports.modele','xlsx') }}">Excel</a> · <a href="{{ route('imports.modele','csv') }}">CSV</a>. Dates au format JJ/MM/AAAA. Aucun candidat n'est enregistré avant votre confirmation sur l'écran d'aperçu.</p>
<form method="post" action="{{ route('imports.store') }}" enctype="multipart/form-data" class="row g-2">@csrf
<div class="col-md-4"><select name="session_id" class="form-select" required><option value="">Session…</option>@foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->examen->code }} — {{ $s->annee }}</option>@endforeach</select></div>
<div class="col-md-5"><input type="file" name="fichier" class="form-control" accept=".xlsx,.xls,.csv,.txt" required></div>
<div class="col-md-3"><button class="btn btn-primary w-100">Analyser le fichier</button></div></form></div>
<table class="table table-sm bg-white"><thead><tr><th>Date</th><th>Fichier</th><th>Session</th><th>Lignes</th><th>Importés</th><th>État</th><th></th></tr></thead><tbody>
@forelse($imports as $i)<tr><td>{{ $i->created_at->format('d/m/Y H:i') }}</td><td>{{ $i->fichier_original }}</td><td>{{ $i->session->examen->code }} {{ $i->session->annee }}</td><td>{{ $i->total }}</td><td>{{ $i->importes }}</td><td>{{ ['apercu'=>'En aperçu','termine'=>'Terminé','annule'=>'Annulé'][$i->statut] }}</td>
<td><a class="btn btn-sm btn-outline-primary" href="{{ route('imports.show',$i) }}">Ouvrir</a></td></tr>@empty<tr><td colspan="7" class="text-center text-muted">Aucun import.</td></tr>@endforelse</tbody></table>{{ $imports->links() }}
@endsection
