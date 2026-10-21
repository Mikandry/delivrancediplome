@extends('layouts.app')
@section('titre', "Journal d'audit")
@section('contenu')
<form class="row g-2 mb-3"><div class="col-md-3"><input name="action" value="{{ request('action') }}" class="form-control" placeholder="Action (ex. diplome., auth.)"></div>
<div class="col-md-2"><input type="date" name="du" value="{{ request('du') }}" class="form-control"></div><div class="col-md-2"><input type="date" name="au" value="{{ request('au') }}" class="form-control"></div><div class="col-auto"><button class="btn btn-primary">Filtrer</button></div></form>
<table class="table table-sm bg-white small"><thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Objet</th><th>Motif</th><th>Avant → Après</th><th>IP</th></tr></thead><tbody>
@foreach($logs as $l)<tr><td>{{ $l->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $l->user->name ?? '—' }}</td><td>{{ $l->action }}</td><td>{{ $l->entite }} {{ $l->entite_id }}</td><td>{{ $l->motif }}</td>
<td>@if($l->anciennes || $l->nouvelles)<code>{{ \Illuminate\Support\Str::limit(json_encode($l->anciennes, JSON_UNESCAPED_UNICODE), 80) }} → {{ \Illuminate\Support\Str::limit(json_encode($l->nouvelles, JSON_UNESCAPED_UNICODE), 80) }}</code>@endif</td><td>{{ $l->ip }}</td></tr>@endforeach</tbody></table>{{ $logs->links() }}
@endsection
