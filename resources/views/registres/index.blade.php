@extends('layouts.app')
@section('titre', 'Registres de notes')
@section('contenu')
<div class="card card-body mb-4"><h6>Nouveau registre</h6>
<form class="row g-2"><div class="col-md-6"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Nom, n° d'inscription ou n° de candidat"></div><div class="col-auto"><button class="btn btn-primary">Rechercher</button></div></form>
@foreach($cands as $c)<div class="d-flex justify-content-between border-bottom py-1 small"><span>{{ $c->nomComplet() }} — {{ $c->examen->code }} {{ $c->session->annee }} — n° {{ $c->numero_inscription }}</span><a class="btn btn-sm btn-outline-primary" href="{{ route('registres.create',$c) }}">Saisir les notes</a></div>@endforeach</div>
<table class="table table-sm bg-white"><thead><tr><th>N°</th><th>Candidat</th><th>Examen</th><th>Établi le</th><th></th></tr></thead><tbody>
@forelse($registres as $g)<tr><td>{{ $g->id }}</td><td>{{ $g->candidat->nomComplet() }}</td><td>{{ $g->candidat->examen->code }} {{ $g->candidat->session->annee }}</td><td>{{ $g->date_etablissement->format('d/m/Y') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('registres.show',$g) }}">Ouvrir</a></td></tr>
@empty<tr><td colspan="5" class="text-center text-muted">Aucun registre.</td></tr>@endforelse</tbody></table>{{ $registres->links() }}
@endsection
