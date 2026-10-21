@extends('layouts.app')
@section('titre', 'Demande ' . $d->numero)
@section('contenu')
@php
  $etapes = config('workflow.etapes_client'); $groupe = config('workflow.regroupement_client');
  $courant = $groupe[$d->statut] ?? $d->statut; $pos = array_search($courant, $etapes);
@endphp
@if($client)
<div class="card card-body mb-3"><div class="d-flex flex-wrap gap-2 align-items-center">
@foreach($etapes as $i=>$e)<span class="badge {{ $d->statut==='rejete' ? 'text-bg-light' : ($pos!==false && $i<$pos ? 'text-bg-success' : ($i===$pos ? 'text-bg-primary' : 'text-bg-light border')) }}">{{ $i+1 }}. {{ config('workflow.statuts')[$e] }}</span>@if(!$loop->last)<span class="text-muted">›</span>@endif @endforeach</div>
@if($d->statut==='rejete')<div class="alert alert-danger mt-3 mb-0">Demande rejetée.</div>@endif</div>
@endif
<div class="row g-3"><div class="col-lg-6"><div class="card card-body"><h6>{{ $d->nomComplet() }}</h6>
<dl class="row small mb-0">
<dt class="col-5">Diplôme / session</dt><dd class="col-7">{{ $d->examen->code }} {{ $d->session->annee }}</dd>
<dt class="col-5">Né(e) le</dt><dd class="col-7">{{ $d->date_naissance->format('d/m/Y') }} à {{ $d->lieu_naissance }}</dd>
<dt class="col-5">Reçue le</dt><dd class="col-7">{{ $d->recu_at->format('d/m/Y H:i') }}</dd>
<dt class="col-5">Statut</dt><dd class="col-7"><strong>{{ $d->libelleStatut() }}</strong></dd>
<dt class="col-5">Délivrance prévue</dt><dd class="col-7">@if($d->date_prevue && !$manquantes){{ $d->date_prevue->format('d/m/Y') }} <span class="text-muted">(prévisionnelle)</span>@else<span class="text-muted">Non annoncée : dossier incomplet</span>@endif</dd>
@if($client)<dt class="col-5">Code de suivi</dt><dd class="col-7"><code>{{ $d->codeSuiviClair() }}</code></dd>@endif
@if(!$client)<dt class="col-5">Candidat associé</dt><dd class="col-7">{{ $d->candidat ? $d->candidat->nomComplet().' ('.$d->candidat->numero_inscription.')' : 'Aucun' }}</dd>@endif
@if(!$client && $d->diplome)<dt class="col-5">Diplôme</dt><dd class="col-7"><a href="{{ route('diplomes.show',$d->diplome) }}">{{ $d->diplome->numero_diplome }}</a></dd>@endif
</dl><div class="mt-3"><a class="btn btn-outline-secondary btn-sm" target="_blank" href="{{ route('demandes.quittance',$d) }}">Quittance de réception (PDF)</a></div></div></div>

<div class="col-lg-6"><div class="card card-body"><h6>Pièces du dossier</h6>
<ul class="small">@forelse($d->pieces as $p)<li><a href="{{ route('pieces.show',$p) }}">{{ $p->libelle() }}</a> — {{ $p->nom_original }}</li>@empty<li class="text-muted">Aucune pièce.</li>@endforelse</ul>
@if($manquantes)<div class="alert alert-warning py-2 small">Pièces manquantes : {{ implode(', ', $manquantes) }}</div>@endif
@if($client && in_array($d->statut,['attente_pieces','recue']))
<form method="post" action="{{ route('demandes.pieces',$d) }}" enctype="multipart/form-data" class="row g-2">@csrf
<div class="col-5"><select name="type" class="form-select form-select-sm">@foreach(config('workflow.pieces') as $t=>$def)<option value="{{ $t }}">{{ $def['label'] }}</option>@endforeach</select></div>
<div class="col-5"><input type="file" name="fichier" class="form-control form-control-sm" required></div><div class="col-2"><button class="btn btn-sm btn-primary w-100">Ajouter</button></div></form>@endif
</div></div></div>

@if(!$client)
@if($correspondances->count())<div class="card card-body mt-3"><h6>Candidats correspondants dans la liste officielle</h6>
@foreach($correspondances as $c)<form method="post" action="{{ route('demandes.associer',$d) }}" class="d-flex justify-content-between border-bottom py-1 small">@csrf<input type="hidden" name="candidat_id" value="{{ $c->id }}"><span>{{ $c->nomComplet() }} — né(e) le {{ $c->date_naissance->format('d/m/Y') }} à {{ $c->lieu_naissance }} — n° {{ $c->numero_inscription }}</span><button class="btn btn-sm btn-outline-primary">Associer</button></form>@endforeach
<p class="small text-muted mt-2 mb-0">Association décidée par l'agent : aucune fusion automatique.</p></div>
@elseif(!$d->candidat)<div class="alert alert-warning mt-3 small">Aucun candidat correspondant trouvé dans la liste de cette session. Le saisisseur doit vérifier la liste (statut « non retrouvé » ne signifie pas « non admis »).</div>@endif

@if($transitions)<div class="card card-body mt-3"><h6>Actions</h6>
@foreach($transitions as $vers=>$t)<form method="post" action="{{ route('demandes.transition',$d) }}" class="mb-2" onsubmit="return confirm('Confirmer : {{ $t['label'] }} ?')">@csrf<input type="hidden" name="vers" value="{{ $vers }}">
<div class="input-group">@if($t['motif'])<input name="motif" class="form-control" placeholder="Motif obligatoire" required><span class="input-group-text"><label class="mb-0 small"><input type="checkbox" name="visible_client" value="1"> visible par le demandeur</label></span>@else<input name="motif" class="form-control" placeholder="Commentaire (facultatif)">@endif
<button class="btn {{ in_array($vers,['rejete','attente_correction']) ? 'btn-outline-danger' : 'btn-primary' }}">{{ $t['label'] }}</button></div></form>@endforeach</div>@endif
@endif

<h6 class="mt-4">Historique</h6>
<table class="table table-sm bg-white"><thead><tr><th>Date</th><th>Étape</th><th>Par</th><th>Motif / note</th></tr></thead><tbody>
@foreach($d->historique as $h)@if(!$client || $h->visible_client || !$h->motif)<tr><td>{{ $h->created_at->format('d/m/Y H:i') }}</td><td>{{ config('workflow.statuts')[$h->vers] ?? $h->vers }}</td><td>{{ $client ? '' : ($h->user->name ?? 'Système') }}</td><td>{{ $h->motif }}</td></tr>@endif @endforeach</tbody></table>
@endsection
