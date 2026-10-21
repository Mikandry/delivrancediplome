@extends('layouts.app')
@section('titre', 'Diplômes')
@section('contenu')
<form class="row g-2 mb-3"><div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="N° de diplôme ou nom"></div>
<div class="col-md-2"><select name="examen_id" class="form-select"><option value="">Tous examens</option>@foreach($examens as $e)<option value="{{ $e->id }}" @selected(request('examen_id')==$e->id)>{{ $e->code }}</option>@endforeach</select></div>
<div class="col-md-3"><select name="statut" class="form-select"><option value="">Tous statuts</option>@foreach(['brouillon','valide_pour_impression','imprime','delivre','revoque','remplace'] as $s)<option value="{{ $s }}" @selected(request('statut')==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
<div class="col-auto"><button class="btn btn-primary">Filtrer</button></div></form>
<table class="table table-sm bg-white align-middle"><thead><tr><th>N° diplôme</th><th>Candidat</th><th>Examen</th><th>Statut</th><th>Signé le</th><th></th></tr></thead><tbody>
@forelse($diplomes as $d)<tr><td>{{ $d->numero_diplome }}</td><td>{{ $d->candidat->nomComplet() }}</td><td>{{ $d->candidat->examen->code }} {{ $d->candidat->session->annee }}</td><td>{{ $d->libelleStatut() }}</td><td>{{ $d->date_signature?->format('d/m/Y H:i') ?? '—' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('diplomes.show',$d) }}">Ouvrir</a></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted">Aucun diplôme.</td></tr>@endforelse</tbody></table>{{ $diplomes->links() }}
@endsection
