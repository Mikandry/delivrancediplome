@extends('layouts.app')
@section('titre', 'Utilisateurs')
@section('contenu')
<div class="card card-body mb-4"><h6>Nouveau compte</h6>
<form method="post" action="{{ route('users.store') }}" class="row g-2">@csrf
<div class="col-md-3"><input name="name" class="form-control" placeholder="Nom complet" required></div>
<div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
<div class="col-md-2"><select name="role" class="form-select">@foreach(config('permissions.roles') as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
<div class="col-md-3"><input type="password" name="password" class="form-control" placeholder="Mot de passe temporaire (12+)" required></div>
<div class="col-md-1"><button class="btn btn-primary w-100">Créer</button></div></form></div>
<table class="table table-sm bg-white align-middle"><thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>État</th><th></th></tr></thead><tbody>
@foreach($users as $u)<tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td>
<td><form method="post" action="{{ route('users.update', $u) }}" class="d-flex gap-1">@csrf @method('PUT')
<select name="role" class="form-select form-select-sm" @disabled($u->id===auth()->id())>@foreach(config('permissions.roles') as $k=>$l)<option value="{{ $k }}" @selected($u->role===$k)>{{ $l }}</option>@endforeach</select>
@if($u->id!==auth()->id())<button class="btn btn-sm btn-outline-primary">OK</button>@endif</form></td>
<td>{!! $u->actif ? '<span class="badge text-bg-success">Actif</span>' : '<span class="badge text-bg-secondary">Désactivé</span>' !!}</td>
<td>@if($u->id!==auth()->id())<form method="post" action="{{ route('users.toggle', $u) }}" onsubmit="return confirm('Confirmer ?')">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $u->actif ? 'Désactiver' : 'Réactiver' }}</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $users->links() }}
@endsection
