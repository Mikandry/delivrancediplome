<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vérification d'un document officiel</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5" style="max-width:560px">
<h5 class="text-center">Ministère de l'Éducation nationale</h5><p class="text-center text-muted">Vérification d'authenticité d'un document</p>
@if(!$doc)
<div class="alert alert-danger"><strong>Document non reconnu.</strong> Ce code ne correspond à aucun document officiel enregistré. Cela ne constitue pas, à lui seul, une preuve de non-admission.</div>
@else
@php $c = $doc->candidat; @endphp
@if($type==='diplome')
 @php $ok = in_array($doc->statut, ['valide_pour_impression','imprime','delivre']); @endphp
 <div class="alert alert-{{ $ok ? 'success' : 'warning' }}"><strong>{{ $ok ? 'Diplôme authentique et valide' : 'Diplôme ' . mb_strtolower($doc->libelleStatut()) }}</strong></div>
 @php $lignes = ['N° du diplôme' => $doc->numero_diplome, 'Diplôme' => $c->examen->code, 'Session' => $c->session->annee, 'Émis le' => $doc->date_signature?->format('d/m/Y')]; @endphp
@elseif($type==='attestation')
 @php $ok = $doc->statut === 'valide' && (!$doc->valide_jusqu || $doc->valide_jusqu->gte(today())); @endphp
 <div class="alert alert-{{ $ok ? 'success' : 'warning' }}"><strong>{{ $doc->libelleType() }} — {{ $ok ? 'authentique et valide' : ($doc->statut !== 'valide' ? $doc->statut : 'expirée') }}</strong>@if($doc->type==='provisoire')<br>Document PROVISOIRE : il ne constitue pas le diplôme.@endif</div>
 @php $lignes = ['N° de l\'attestation' => $doc->numero, 'Examen' => $c->examen->code, 'Session' => $c->session->annee, 'Émise le' => $doc->emise_at->format('d/m/Y'), 'Valide jusqu\'au' => $doc->valide_jusqu?->format('d/m/Y')]; @endphp
@else
 <div class="alert alert-success"><strong>Registre de notes authentique</strong><br>Le détail des notes n'est pas publié.</div>
 @php $lignes = ['Examen' => $c->examen->code, 'Session' => $c->session->annee, 'Établi le' => $doc->date_etablissement->format('d/m/Y')]; @endphp
@endif
<div class="card"><dl class="row m-3 mb-3">
@foreach($lignes as $l=>$v)@if($v)<dt class="col-5">{{ $l }}</dt><dd class="col-7">{{ $v }}</dd>@endif @endforeach
<dt class="col-5">Titulaire</dt><dd class="col-7">{{ mb_strtoupper($c->nom) }} {{ config('diplomes.masquer_prenoms_verification') ? mb_substr($c->prenoms,0,1).'.' : $c->prenoms }}</dd></dl></div>
@endif
</div></body></html>
