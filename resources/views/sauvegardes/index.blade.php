@extends('layouts.app')
@section('titre', 'Sauvegardes de la base')
@section('contenu')
<form method="post" action="{{ route('sauvegardes.store') }}" class="mb-3">@csrf<button class="btn btn-primary">Créer une sauvegarde maintenant</button></form>
<p class="small text-muted">Sauvegarde automatique quotidienne à 02:00 si le planificateur est actif (voir le guide). La <strong>restauration se fait uniquement en ligne de commande</strong> : <code>php artisan diplomes:restaurer NOM_DU_FICHIER</code>. Copiez régulièrement les fichiers hors du serveur.</p>
<table class="table table-sm bg-white" style="max-width:720px"><thead><tr><th>Fichier</th><th>Date</th><th>Taille</th><th></th></tr></thead><tbody>
@forelse($fichiers as $f)<tr><td>{{ $f['nom'] }}</td><td>{{ date('d/m/Y H:i', $f['date']) }}</td><td>{{ number_format($f['taille']/1024,0,',',' ') }} Ko</td><td><a class="btn btn-sm btn-outline-secondary" href="{{ route('sauvegardes.download',$f['nom']) }}">Télécharger</a></td></tr>@empty<tr><td colspan="4" class="text-muted text-center">Aucune sauvegarde.</td></tr>@endforelse</tbody></table>
@endsection
