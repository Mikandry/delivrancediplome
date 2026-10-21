@extends('layouts.app')
@section('titre', $client ? 'Mes demandes' : 'Demandes de diplômes')
@section('contenu')
@if($client)<a href="{{ route('demandes.create') }}" class="btn btn-success mb-3">+ Nouvelle demande</a>
@else<form class="row g-2 mb-3"><div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="N° de demande ou nom"></div>
<div class="col-md-2"><select name="examen_id" class="form-select"><option value="">Tous examens</option>@foreach($examens as $e)<option value="{{ $e->id }}" @selected(request('examen_id')==$e->id)>{{ $e->code }}</option>@endforeach</select></div>
<div class="col-md-3"><select name="statut" class="form-select"><option value="">Tous statuts</option>@foreach(config('workflow.statuts') as $k=>$l)<option value="{{ $k }}" @selected(request('statut')==$k)>{{ $l }}</option>@endforeach</select></div>
<div class="col-auto form-check pt-2 ms-2"><input type="checkbox" class="form-check-input" name="retard" value="1" id="rt" @checked(request('retard'))><label for="rt" class="form-check-label">Hors délai</label></div>
<div class="col-auto"><button class="btn btn-primary">Filtrer</button></div></form>@endif
<table class="table table-sm bg-white align-middle"><thead><tr><th>N°</th><th>Demandeur</th><th>Examen</th><th>Reçue le</th><th>Statut</th><th>Délivrance prévue</th><th></th></tr></thead><tbody>
@forelse($demandes as $d)<tr><td>{{ $d->numero }}</td><td>{{ $d->nomComplet() }}</td><td>{{ $d->examen->code }} {{ $d->session->annee }}</td><td>{{ $d->recu_at->format('d/m/Y H:i') }}</td>
<td>{{ $d->libelleStatut() }} @if($d->enRetard())<span class="badge text-bg-danger">Hors délai</span>@endif</td><td>{{ $d->date_prevue?->format('d/m/Y') ?? '—' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('demandes.show',$d) }}">Ouvrir</a></td></tr>
@empty<tr><td colspan="7" class="text-center text-muted">Aucune demande.</td></tr>@endforelse</tbody></table>{{ $demandes->links() }}
@endsection
