@extends('layouts.app')
@section('titre', 'Candidats')
@section('contenu')
<form class="row g-2 mb-3">
<div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Nom, prénoms, n° d'inscription ou de candidat"></div>
<div class="col-md-2"><select name="examen_id" class="form-select"><option value="">Tous examens</option>@foreach($examens as $e)<option value="{{ $e->id }}" @selected(request('examen_id')==$e->id)>{{ $e->code }}</option>@endforeach</select></div>
<div class="col-md-2"><select name="session_id" class="form-select"><option value="">Toutes sessions</option>@foreach($sessions as $s)<option value="{{ $s->id }}" @selected(request('session_id')==$s->id)>{{ $s->examen->code }} {{ $s->annee }}</option>@endforeach</select></div>
<div class="col-auto"><button class="btn btn-primary">Rechercher</button></div>
@if(auth()->user()->hasPermission('candidats.create'))<div class="col-auto ms-auto"><a href="{{ route('candidats.create') }}" class="btn btn-success">+ Nouveau candidat</a></div>@endif</form>
<table class="table table-sm bg-white align-middle"><thead><tr><th>Nom et prénoms</th><th>Né(e) le</th><th>Examen</th><th>N° inscription</th><th>Diplôme</th><th></th></tr></thead><tbody>
@forelse($candidats as $c)<tr><td>{{ $c->nomComplet() }}</td><td>{{ $c->date_naissance->format('d/m/Y') }} à {{ $c->lieu_naissance }}</td><td>{{ $c->examen->code }} {{ $c->session->annee }}</td><td>{{ $c->numero_inscription }}</td>
<td>@if($c->diplome)<a href="{{ route('diplomes.show', $c->diplome) }}">{{ $c->diplome->numero_diplome }}</a>@elseif(auth()->user()->hasPermission('diplomes.create'))<form method="post" action="{{ route('diplomes.store') }}">@csrf<input type="hidden" name="candidat_id" value="{{ $c->id }}"><button class="btn btn-sm btn-outline-primary">Créer</button></form>@else — @endif</td>
<td>@if(auth()->user()->hasPermission('candidats.edit'))<a class="btn btn-sm btn-outline-secondary" href="{{ route('candidats.edit', $c) }}">{{ $c->estVerrouille() ? '🔒' : 'Modifier' }}</a>@endif</td></tr>
@empty<tr><td colspan="6" class="text-center text-muted">Aucun candidat.</td></tr>@endforelse</tbody></table>{{ $candidats->links() }}
@endsection
