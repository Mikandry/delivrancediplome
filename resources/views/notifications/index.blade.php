@extends('layouts.app')
@section('titre', 'Notifications')
@section('contenu')
<div class="list-group">@forelse($notifs as $n)<a href="{{ $n->lien ?? '#' }}" class="list-group-item list-group-item-action"><div class="d-flex justify-content-between"><strong>{{ $n->titre }}</strong><small class="text-muted">{{ $n->created_at->format('d/m/Y H:i') }}</small></div>{{ $n->message }}</a>@empty<div class="text-muted">Aucune notification.</div>@endforelse</div>{{ $notifs->links() }}
@endsection
