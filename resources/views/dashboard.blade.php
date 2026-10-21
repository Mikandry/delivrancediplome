@extends('layouts.app')
@section('titre', 'Tableau de bord')
@section('contenu')
@php $cards = [['Candidats enregistrés',$candidats],['Diplômes créés',$total_diplomes],['Créés aujourd\'hui',$aujourdhui],
 ['Brouillons',$par_statut['brouillon'] ?? 0],['Validés pour impression',$par_statut['valide_pour_impression'] ?? 0],['Imprimés',$par_statut['imprime'] ?? 0],
 ['Impressions totales',$impressions],['Réimpressions autorisées',$reimpressions]]; @endphp
<div class="row g-3">@foreach($cards as [$l,$n])<div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ $l }}</div><div class="fs-2 fw-semibold">{{ $n }}</div></div></div></div>@endforeach</div>
<h6 class="mt-4">Demandes</h6>
<div class="row g-3">@foreach([['Reçues aujourd\'hui',$dem_jour],['Reçues ce mois',$dem_mois],['Reçues cette année',$dem_annee],['Hors délai prévu',$dem_retard]] as [$l,$n])<div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ $l }}</div><div class="fs-2 fw-semibold">{{ $n }}</div></div></div></div>@endforeach</div>
<table class="table table-sm bg-white mt-3" style="max-width:520px"><thead><tr><th>Statut</th><th>Dossiers</th></tr></thead><tbody>
@forelse($dem_statut as $st=>$n)<tr><td>{{ config('workflow.statuts')[$st] ?? $st }}</td><td>{{ $n }}</td></tr>@empty<tr><td colspan="2" class="text-muted">Aucune demande.</td></tr>@endforelse</tbody></table>
<p class="text-muted small mt-3">Chiffres issus de la base de données. Les statistiques détaillées (délais, par agent) arrivent avec le lot 3.</p>
@endsection
