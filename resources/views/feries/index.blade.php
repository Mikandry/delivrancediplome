@extends('layouts.app')
@section('titre', 'Jours fériés et exceptions administratives')
@section('contenu')
<p class="small text-muted">Ces jours sont exclus du calcul du délai de {{ config('workflow.delai_jours_ouvres') }} jours ouvrés, avec les samedis et dimanches. Ajoutez aussi les fêtes mobiles et les fermetures exceptionnelles. Initialisation : <code>php artisan diplomes:feries 2026</code></p>
<form method="post" action="{{ route('feries.store') }}" class="row g-2 mb-3">@csrf
<div class="col-md-3"><input type="date" name="jour" class="form-control" required></div><div class="col-md-5"><input name="libelle" class="form-control" placeholder="Libellé" required></div><div class="col-md-2"><button class="btn btn-primary">Ajouter</button></div></form>
<table class="table table-sm bg-white" style="max-width:640px"><tbody>@forelse($feries as $f)<tr><td>{{ $f->jour->format('d/m/Y') }}</td><td>{{ $f->libelle }}</td>
<td><form method="post" action="{{ route('feries.destroy',$f) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Supprimer</button></form></td></tr>@empty<tr><td class="text-muted">Aucun jour férié enregistré : le délai ne tient compte que des week-ends.</td></tr>@endforelse</tbody></table>
@endsection
