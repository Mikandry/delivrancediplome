<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Suivi d'une demande</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5" style="max-width:520px">
<h5 class="text-center">Suivi d'une demande de diplôme</h5><p class="text-center text-muted small">Direction des examens et de la certification</p>
@if(!empty($echec))<div class="alert alert-danger">Numéro ou code de suivi incorrect.</div>@endif
@if($demande)@php $dd=$demande; @endphp
<div class="card card-body mb-3"><dl class="row mb-0"><dt class="col-5">N° de demande</dt><dd class="col-7">{{ $dd->numero }}</dd>
<dt class="col-5">Diplôme</dt><dd class="col-7">{{ $dd->examen->code ?? '' }}</dd><dt class="col-5">Demandeur</dt><dd class="col-7">{{ mb_strtoupper(mb_substr($dd->nom,0,1)) }}. {{ mb_strtoupper(mb_substr($dd->prenoms,0,1)) }}.</dd>
<dt class="col-5">Reçue le</dt><dd class="col-7">{{ $dd->recu_at->format('d/m/Y') }}</dd><dt class="col-5">Statut</dt><dd class="col-7"><strong>{{ config('workflow.statuts')[config('workflow.regroupement_client')[$dd->statut] ?? $dd->statut] }}</strong></dd>
<dt class="col-5">Délivrance prévue</dt><dd class="col-7">{{ ($dd->date_prevue && $dd->estComplete()) ? $dd->date_prevue->format('d/m/Y').' (prévisionnelle)' : 'Non annoncée (dossier incomplet)' }}</dd></dl></div>
@else
<form method="post" action="{{ route('suivi.consulter') }}" class="card card-body">@csrf
<div class="mb-3"><label class="form-label">N° de demande</label><input name="numero" value="{{ $numero ?? '' }}" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Code de suivi</label><input name="code" class="form-control" required autocomplete="off"></div>
<button class="btn btn-primary w-100">Consulter</button></form>@endif
<div class="text-center small mt-3"><a href="{{ route('login') }}">Espace connecté</a></div></div></body></html>
