@extends('layouts.app')
@section('titre', 'Diplôme ' . $d->numero_diplome)
@section('contenu')
@php $u = auth()->user(); $c = $d->candidat; $dispo = $d->autorisationDisponible(); @endphp
<div class="row g-3"><div class="col-lg-6"><div class="card card-body">
<h6>{{ $c->nomComplet() }}</h6>
<dl class="row mb-0 small">
<dt class="col-5">Examen / session</dt><dd class="col-7">{{ $c->examen->code }} {{ $c->session->annee }}</dd>
<dt class="col-5">Né(e) le</dt><dd class="col-7">{{ $c->date_naissance->format('d/m/Y') }} à {{ $c->lieu_naissance }}</dd>
<dt class="col-5">N° d'inscription</dt><dd class="col-7">{{ $c->numero_inscription }}</dd>
<dt class="col-5">Centre d'écrit</dt><dd class="col-7">{{ $c->centre_ecrit }}</dd>
<dt class="col-5">Commission siégeant à</dt><dd class="col-7">{{ $c->lycee_siegeant }}</dd>
<dt class="col-5">PV de délivrance</dt><dd class="col-7">{{ $c->session->pv_date?->format('d/m/Y') ?? 'non renseigné' }}</dd>
<dt class="col-5">Statut</dt><dd class="col-7"><strong>{{ $d->libelleStatut() }}</strong></dd>
<dt class="col-5">Date de signature</dt><dd class="col-7">{{ $d->date_signature?->format('d/m/Y H:i') ?? 'Posée à la validation' }}</dd>
</dl></div></div>
<div class="col-lg-6"><div class="card card-body">
<h6>Actions</h6>
<a class="btn btn-outline-secondary mb-2" target="_blank" href="{{ route('diplomes.apercu',$d) }}">Aperçu SPÉCIMEN (non imprimable officiellement)</a>
@if($d->statut==='brouillon' && $u->hasPermission('diplomes.signer'))
<form method="post" action="{{ route('diplomes.signer',$d) }}" onsubmit="return confirm('Valider définitivement ? La date de signature sera enregistrée automatiquement.')">@csrf<button class="btn btn-success w-100 mb-2">Valider pour impression (signature)</button></form>@endif
@if($u->hasPermission('diplomes.imprimer'))
 @if($d->statut==='valide_pour_impression' || ($d->nb_impressions>0 && $dispo))
 <form method="post" target="_blank" action="{{ route('diplomes.imprimer',$d) }}" onsubmit="return confirm('Cette impression sera définitivement enregistrée. Continuer ?')">@csrf<button class="btn btn-primary w-100 mb-2">{{ $d->nb_impressions>0 ? 'Imprimer la réimpression autorisée' : 'Imprimer le diplôme' }}</button></form>
 @elseif($d->nb_impressions>0)<button class="btn btn-secondary w-100 mb-2" disabled>Déjà imprimé ({{ $d->nb_impressions }})</button>@endif
@endif
@if($u->isSuperAdmin() && $d->statut==='imprime' && !$dispo)
<form method="post" action="{{ route('diplomes.reimpression',$d) }}" class="border rounded p-2">@csrf<label class="form-label small">Autoriser une réimpression exceptionnelle — motif (10 car. min.)</label>
<textarea name="motif" class="form-control mb-2" rows="2" required></textarea><button class="btn btn-warning btn-sm">Autoriser (usage unique)</button></form>@endif
</div></div></div>
<h6 class="mt-4">Historique des impressions</h6>
<table class="table table-sm bg-white"><thead><tr><th>Rang</th><th>Date</th><th>Agent</th><th>Nature</th></tr></thead><tbody>
@forelse($d->impressions as $i)<tr><td>{{ $i->rang }}</td><td>{{ $i->imprime_at->format('d/m/Y H:i') }}</td><td>{{ $i->user->name }}</td><td>{{ $i->rang==1 ? 'Original' : 'Réimpression n°'.($i->rang-1) }}</td></tr>@empty<tr><td colspan="4" class="text-muted">Aucune impression.</td></tr>@endforelse</tbody></table>
@endsection
