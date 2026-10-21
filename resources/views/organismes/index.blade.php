@extends('layouts.app')
@section('titre', 'Organismes habilités à vérifier')
@section('contenu')
@if(session('cle_generee'))<div class="alert alert-warning"><strong>Clé d'accès (affichée une seule fois) :</strong> <code class="user-select-all">{{ session('cle_generee') }}</code><br><small>À transmettre à l'organisme par un canal sûr. Elle n'est pas conservée en clair.</small></div>@endif
<div class="card card-body mb-4"><h6>Nouvel organisme</h6><p class="small text-muted mb-2">Les organismes utilisent la page publique <code>/verification-organisme</code> avec leur clé.</p>
<form method="post" action="{{ route('organismes.store') }}" class="row g-2">@csrf
<div class="col-md-4"><input name="nom" class="form-control" placeholder="Nom de l'organisme" required></div><div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email de contact"></div>
<div class="col-md-3"><input type="number" name="limite_heure" value="500" class="form-control" title="Requêtes par heure"></div><div class="col-md-2"><button class="btn btn-primary w-100">Créer</button></div></form></div>
<table class="table table-sm bg-white align-middle"><thead><tr><th>Organisme</th><th>Clé</th><th>Limite/h</th><th>Dernier usage</th><th>État</th><th></th></tr></thead><tbody>
@forelse($organismes as $o)<tr><td>{{ $o->nom }}<div class="small text-muted">{{ $o->email }}</div></td><td><code>{{ $o->cle_prefixe }}…</code></td><td>{{ $o->limite_heure }}</td><td>{{ $o->dernier_usage_at?->format('d/m/Y H:i') ?? '—' }}</td><td>{{ $o->actif ? 'Actif' : 'Désactivé' }}</td>
<td><form method="post" action="{{ route('organismes.toggle',$o) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $o->actif ? 'Désactiver' : 'Réactiver' }}</button></form></td></tr>@empty<tr><td colspan="6" class="text-center text-muted">Aucun organisme.</td></tr>@endforelse</tbody></table>
@endsection
