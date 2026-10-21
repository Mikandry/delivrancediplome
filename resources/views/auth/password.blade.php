@extends('layouts.app')
@section('titre', 'Changer le mot de passe')
@section('contenu')
<form method="post" action="{{ route('password.update') }}" class="card card-body" style="max-width:480px">@csrf @method('PUT')
<p class="small text-muted">12 caractères minimum, avec majuscules, minuscules, chiffres et symboles.</p>
<div class="mb-3"><label class="form-label">Mot de passe actuel</label><input type="password" name="current_password" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Nouveau mot de passe</label><input type="password" name="password" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Confirmation</label><input type="password" name="password_confirmation" class="form-control" required></div>
<button class="btn btn-primary">Enregistrer</button></form>
@endsection
