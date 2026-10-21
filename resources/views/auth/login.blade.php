<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh"><div class="container" style="max-width:420px">
<div class="card shadow-sm"><div class="card-body p-4">
<h5 class="text-center mb-1">Ministère de l'Éducation nationale</h5>
<p class="text-center text-muted small">Direction des examens et de la certification</p>
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('login') }}">@csrf
<div class="mb-3"><label class="form-label">Adresse email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus></div>
<div class="mb-3"><label class="form-label">Mot de passe</label><input type="password" name="password" class="form-control" required></div>
<button class="btn btn-primary w-100">Se connecter</button></form>
<hr><div class="text-center small"><a href="{{ route('register') }}">Créer un compte demandeur</a> · <a href="{{ route('suivi') }}">Suivre une demande</a></div>
</div></div></div></body></html>
