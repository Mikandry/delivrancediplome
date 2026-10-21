@extends('layouts.app')
@section('titre', $c->exists ? 'Corriger un candidat' : 'Nouveau candidat')
@section('contenu')
@if($c->exists && $c->estVerrouille() && !auth()->user()->hasPermission('candidats.edit_locked'))<div class="alert alert-warning">🔒 Données verrouillées : modification impossible sans autorisation de l'administrateur.</div>@endif
<form method="post" action="{{ $c->exists ? route('candidats.update', $c) : route('candidats.store') }}" class="card card-body">@csrf @if($c->exists) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Examen / session</label><select name="session_id" class="form-select" required>@foreach($sessions as $s)<option value="{{ $s->id }}" @selected(old('session_id',$c->session_id)==$s->id)>{{ $s->examen->code }} — {{ $s->annee }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Nom</label><input name="nom" class="form-control" value="{{ old('nom',$c->nom) }}" required></div>
<div class="col-md-4"><label class="form-label">Prénoms</label><input name="prenoms" class="form-control" value="{{ old('prenoms',$c->prenoms) }}" required></div>
<div class="col-md-3"><label class="form-label">Date de naissance</label><input type="date" name="date_naissance" class="form-control" value="{{ old('date_naissance',$c->date_naissance?->format('Y-m-d')) }}" required></div>
<div class="col-md-3"><label class="form-label">Lieu de naissance</label><input name="lieu_naissance" class="form-control" value="{{ old('lieu_naissance',$c->lieu_naissance) }}" required></div>
<div class="col-md-2"><label class="form-label">Sexe</label><select name="sexe" class="form-select"><option value="">—</option><option value="M" @selected(old('sexe',$c->sexe)=='M')>M</option><option value="F" @selected(old('sexe',$c->sexe)=='F')>F</option></select></div>
<div class="col-md-4"><label class="form-label">N° de candidat</label><input name="numero_candidat" class="form-control" value="{{ old('numero_candidat',$c->numero_candidat) }}"></div>
<div class="col-md-4"><label class="form-label">N° d'inscription</label><input name="numero_inscription" class="form-control" value="{{ old('numero_inscription',$c->numero_inscription) }}" required></div>
<div class="col-md-4"><label class="form-label">Centre d'écrit</label><input name="centre_ecrit" class="form-control" value="{{ old('centre_ecrit',$c->centre_ecrit) }}" required></div>
<div class="col-md-4"><label class="form-label">Lycée d'accueil (commission siégeant à)</label><input name="lycee_siegeant" class="form-control" value="{{ old('lycee_siegeant',$c->lycee_siegeant) }}" required></div>
<div class="col-md-4"><label class="form-label">Mention (facultatif)</label><input name="mention" class="form-control" value="{{ old('mention',$c->mention) }}"></div>
@if($c->exists)<div class="col-12"><label class="form-label">Motif de la correction (obligatoire)</label><input name="motif" class="form-control" value="{{ old('motif') }}" required></div>
@elseif(session('doublon'))<div class="col-12 form-check ms-2"><input type="checkbox" class="form-check-input" name="confirmer_doublon" value="1" id="cd"><label for="cd" class="form-check-label">Je confirme qu'il s'agit d'une personne différente.</label></div>@endif
</div><div class="mt-3"><button class="btn btn-primary">Enregistrer</button> <a href="{{ route('candidats.index') }}" class="btn btn-link">Annuler</a></div></form>
@endsection
