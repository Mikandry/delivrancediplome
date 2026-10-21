<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('titre', 'Gestion des diplômes') — Direction des examens</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
 body{background:#f4f6f9} .side{width:240px;min-height:100vh;background:#16324f}
 .side a{color:#cfd9e6;text-decoration:none;display:block;padding:.6rem 1.2rem} .side a:hover,.side a.on{background:#24507c;color:#fff}
 .brand{color:#fff;padding:1rem 1.2rem;font-weight:600;border-bottom:1px solid #24507c}
</style>
</head>
<body>
<div class="d-flex">
 <nav class="side flex-shrink-0">
  <div class="brand">Diplômes officiels<br><small class="fw-normal">Min. Éducation nationale</small></div>
  @php $u = auth()->user(); @endphp
  <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'on' : '' }}">Tableau de bord</a>
  @if($u->hasPermission('candidats.view'))<a href="{{ route('candidats.index') }}" class="{{ request()->routeIs('candidats.*') ? 'on' : '' }}">Candidats</a>@endif
  @if($u->hasPermission('diplomes.view'))<a href="{{ route('diplomes.index') }}" class="{{ request()->routeIs('diplomes.*') ? 'on' : '' }}">Diplômes</a>@endif
  @if($u->hasPermission('users.manage'))<a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'on' : '' }}">Utilisateurs</a>@endif
  @if($u->hasPermission('demandes.view') || $u->hasPermission('demandes.own'))<a href="{{ route('demandes.index') }}" class="{{ request()->routeIs('demandes.*') ? 'on' : '' }}">{{ $u->role==='client' ? 'Mes demandes' : 'Demandes' }}</a>@endif
  @if($u->hasPermission('demandes.own'))<a href="{{ route('demandes.create') }}">Nouvelle demande</a>@endif
  @if($u->hasPermission('imports.create'))<a href="{{ route('imports.index') }}" class="{{ request()->routeIs('imports.*') ? 'on' : '' }}">Import des admis</a>@endif
  @if($u->hasPermission('sessions.manage'))<a href="{{ route('sessions.index') }}" class="{{ request()->routeIs('sessions.*') ? 'on' : '' }}">Sessions</a>@endif
  @if($u->hasPermission('feries.manage'))<a href="{{ route('feries.index') }}">Jours fériés</a>@endif
  @if($u->hasPermission('attestations.view'))<a href="{{ route('attestations.index') }}" class="{{ request()->routeIs('attestations.*') ? 'on' : '' }}">Attestations</a>@endif
  @if($u->hasPermission('registres.gerer'))<a href="{{ route('registres.index') }}" class="{{ request()->routeIs('registres.*') ? 'on' : '' }}">Registres de notes</a>@endif
  @if($u->hasPermission('stats.view'))<a href="{{ route('stats.index') }}">Statistiques</a>@endif
  @if($u->hasPermission('organismes.manage'))<a href="{{ route('organismes.index') }}">Organismes habilités</a>@endif
  @if($u->hasPermission('audit.view'))<a href="{{ route('audit.index') }}">Journal d'audit</a>@endif
  @if($u->hasPermission('sauvegardes.manage'))<a href="{{ route('sauvegardes.index') }}">Sauvegardes</a>@endif
  @php $nonLues = \App\Models\NotificationInterne::where('user_id',$u->id)->whereNull('lu_at')->count(); @endphp
  <a href="{{ route('notifications.index') }}">Notifications @if($nonLues)<span class="badge text-bg-danger">{{ $nonLues }}</span>@endif</a>
  <a href="{{ route('password.edit') }}">Mon mot de passe</a>
 </nav>
 <main class="flex-grow-1">
  <header class="bg-white border-bottom px-4 py-2 d-flex justify-content-between align-items-center">
   <strong>@yield('titre')</strong>
   <form method="post" action="{{ route('logout') }}" class="d-flex align-items-center gap-3">@csrf
    <span class="text-muted small">{{ $u->name }} — {{ $u->roleLabel() }}</span><button class="btn btn-sm btn-outline-secondary">Déconnexion</button></form>
  </header>
  <div class="p-4">
   @foreach(['success'=>'success','warning'=>'warning'] as $k=>$cls)@if(session($k))<div class="alert alert-{{ $cls }}">{{ session($k) }}</div>@endif @endforeach
   @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
   @yield('contenu')
  </div>
 </main>
</div>
</body></html>
