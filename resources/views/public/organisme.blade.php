<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vérification — organismes habilités</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-4" style="max-width:900px">
<h5 class="text-center">Vérification des admis — organismes habilités</h5><p class="text-center text-muted small">Accès réservé. Chaque requête est journalisée et limitée.</p>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('organisme.verifier') }}" enctype="multipart/form-data" class="card card-body mb-4">@csrf
<div class="mb-3"><label class="form-label">Clé d'accès de l'organisme</label><input type="password" name="cle" class="form-control" required autocomplete="off"></div>
<div class="row g-2"><div class="col-md-4"><input name="nom" class="form-control" placeholder="Nom"></div><div class="col-md-4"><input name="prenoms" class="form-control" placeholder="Prénoms"></div>
<div class="col-md-4"><input name="date_naissance" class="form-control" placeholder="Date de naissance (JJ/MM/AAAA)"></div>
<div class="col-md-4"><input name="numero_inscription" class="form-control" placeholder="N° d'inscription (facultatif)"></div>
<div class="col-md-4"><select name="examen" class="form-select"><option value="">Examen (facultatif)</option><option>BEPC</option><option>CAE.EP</option><option>CAP.EP</option></select></div>
<div class="col-md-4"><input name="annee" class="form-control" placeholder="Année de session"></div></div>
<hr><label class="form-label">Ou vérification en masse : fichier CSV (200 lignes max)</label>
<input type="file" name="fichier" class="form-control" accept=".csv,.txt"><div class="form-text">Colonnes : nom ; prenoms ; date_naissance ; numero_inscription ; examen ; annee</div>
<button class="btn btn-primary mt-3">Vérifier</button></form>
@if($resultats)<h6>Résultats — {{ $organisme->nom }}</h6>
<table class="table table-sm bg-white"><thead><tr><th>Recherche</th><th>Résultat</th><th>Détails</th></tr></thead><tbody>
@foreach($resultats as $x)@php $s=$x['res']['statut']; @endphp<tr><td>{{ mb_strtoupper($x['q']['nom'] ?? '') }} {{ $x['q']['prenoms'] ?? '' }} {{ $x['q']['date_naissance'] ?? '' }}</td>
<td><span class="badge text-bg-{{ ['admis_present'=>'success','non_retrouve'=>'secondary','donnees_insuffisantes'=>'warning','document_invalide'=>'danger','limite'=>'dark'][$s] ?? 'light' }}">{{ $x['res']['libelle'] }}</span></td>
<td class="small">@foreach($x['res']['details'] as $k=>$v){{ ucfirst($k) }} : {{ $v }}<br>@endforeach</td></tr>@endforeach</tbody></table>@endif
</div></body></html>
