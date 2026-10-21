<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Créer un compte</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5" style="max-width:480px"><div class="card shadow-sm"><div class="card-body p-4">
<h5>Créer un compte demandeur</h5>
@if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('register') }}">@csrf
<div class="mb-2"><label class="form-label">Nom et prénoms</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Téléphone</label><input name="telephone" value="{{ old('telephone') }}" class="form-control"></div>
<div class="mb-2"><label class="form-label">Mot de passe (12 car. min., majuscules, chiffres, symboles)</label><input type="password" name="password" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Confirmation</label><input type="password" name="password_confirmation" class="form-control" required></div>
<button class="btn btn-primary w-100">Créer mon compte</button></form>
<div class="text-center small mt-3"><a href="{{ route('login') }}">Déjà un compte ? Se connecter</a></div></div></div></div></body></html>
