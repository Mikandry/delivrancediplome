@extends('layouts.app')
@section('titre', 'Établir une attestation')
@section('contenu')
<div class="card card-body mb-3"><strong>{{ $c->nomComplet() }}</strong><div class="small text-muted">{{ $c->examen->code }} {{ $c->session->annee }} — né(e) le {{ $c->date_naissance->format('d/m/Y') }} à {{ $c->lieu_naissance }} — liste {{ $c->session->verrouillee ? 'approuvée (verrouillée)' : 'non encore approuvée' }}</div></div>
<form method="post" action="{{ route('attestations.store',$c) }}" class="card card-body" style="max-width:640px">@csrf
@foreach(config('attestations.types') as $k=>$l)<div class="form-check mb-2"><input class="form-check-input" type="radio" name="type" value="{{ $k }}" id="t{{ $k }}" @disabled($elig[$k]) @checked(old('type')===$k)>
<label class="form-check-label" for="t{{ $k }}"><strong>{{ $l }}</strong>@if($elig[$k])<div class="small text-danger">{{ $elig[$k] }}</div>@endif</label></div>@endforeach
<div class="mb-3 mt-2"><label class="form-label">Référence (obligatoire pour une perte : déclaration, PV…)</label><textarea name="reference" class="form-control" rows="2">{{ old('reference') }}</textarea></div>
<div><button class="btn btn-primary" onclick="return confirm('Émettre cette attestation ?')">Émettre</button> <a class="btn btn-link" href="{{ route('attestations.index') }}">Annuler</a></div></form>
@endsection
