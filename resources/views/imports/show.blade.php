@extends('layouts.app')
@section('titre', 'Aperçu de l\'import — ' . $imp->fichier_original)
@section('contenu')
<div class="row g-3 mb-3">
<div class="col-md-3"><div class="card card-body"><div class="small text-muted">Lignes valides</div><div class="fs-3 text-success">{{ $stats['ok'] }}</div></div></div>
<div class="col-md-3"><div class="card card-body"><div class="small text-muted">Erreurs (non importées)</div><div class="fs-3 text-danger">{{ $stats['erreurs'] }}</div></div></div>
<div class="col-md-3"><div class="card card-body"><div class="small text-muted">Doublons / ressemblances</div><div class="fs-3 text-warning">{{ $stats['doublons'] }}</div></div></div>
<div class="col-md-3"><div class="card card-body"><div class="small text-muted">Session</div><div class="fs-5">{{ $imp->session->examen->code }} {{ $imp->session->annee }}</div></div></div></div>
<div class="mb-2">Filtrer : <a href="?">Tout</a> · <a href="?filtre=ok">Valides</a> · <a href="?filtre=erreurs">Erreurs</a> · <a href="?filtre=doublons">Doublons</a></div>
<form method="post" action="{{ route('imports.confirmer',$imp) }}">@csrf
<table class="table table-sm bg-white align-middle"><thead><tr><th>Ligne</th><th>Nom et prénoms</th><th>Naissance</th><th>N° inscription</th><th>Centre / Lycée</th><th>Contrôle</th></tr></thead><tbody>
@foreach($lignes as $l)@php $d=$l->donnees; @endphp
<tr class="{{ $l->erreurs ? 'table-danger' : ($l->doublon ? 'table-warning' : '') }}"><td>{{ $l->ligne }}</td><td>{{ mb_strtoupper($d['nom'] ?? '') }} {{ $d['prenoms'] ?? '' }}</td>
<td>{{ !empty($d['date_naissance']) ? \Carbon\Carbon::parse($d['date_naissance'])->format('d/m/Y') : '—' }} {{ $d['lieu_naissance'] ?? '' }}</td><td>{{ $d['numero_inscription'] ?? '' }}</td><td>{{ $d['centre_ecrit'] ?? '' }} / {{ $d['lycee_siegeant'] ?? '' }}</td>
<td>@if($l->erreurs)@foreach($l->erreurs as $e)<div class="small text-danger">{{ $e }}</div>@endforeach
@elseif($l->doublon==='exact')<span class="small">Doublon exact (n° d'inscription déjà en base) — ignoré</span>
@elseif($l->doublon==='fichier')<span class="small">N° présent plusieurs fois dans le fichier — ignoré</span>
@elseif($l->doublon==='ressemblance')<label class="small"><input type="checkbox" name="forcer[]" value="{{ $l->id }}"> Même nom et date de naissance qu'un candidat existant : importer quand même (vérification humaine)</label>
@else<span class="text-success small">OK</span>@endif</td></tr>@endforeach</tbody></table>{{ $lignes->links() }}
@if($imp->statut==='apercu')<p class="small text-muted">Les cases « importer quand même » ne valent que pour la page affichée : confirmez page par page ou traitez les ressemblances avant.</p>
<button class="btn btn-success" onclick="return confirm('Enregistrer définitivement les lignes valides ?')">Confirmer l'import ({{ $stats['ok'] }} lignes valides)</button>
<button class="btn btn-outline-secondary" formaction="{{ route('imports.annuler',$imp) }}">Annuler</button>@endif
</form>
@endsection
