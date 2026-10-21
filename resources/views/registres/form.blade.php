@extends('layouts.app')
@section('titre', $reg->exists ? 'Corriger le registre' : 'Saisie du registre de notes')
@section('contenu')
<div class="card card-body mb-3"><strong>{{ $c->nomComplet() }}</strong><div class="small text-muted">{{ $c->examen->code }} {{ $c->session->annee }} — n° d'inscription {{ $c->numero_inscription }}</div></div>
<p class="small text-muted">Recopiez les notes officielles. Aucun total ni aucune moyenne n'est calculé par le système ; notes de 0 à {{ config('registres.note_max') }}.</p>
<form method="post" action="{{ $reg->exists ? route('registres.update',$reg) : route('registres.store',$c) }}" class="card card-body">@csrf @if($reg->exists) @method('PUT') @endif
<table class="table table-sm"><thead><tr><th>Matière</th><th style="width:110px">Coefficient</th><th style="width:110px">Note</th><th></th></tr></thead><tbody id="corps">
@php $lignes = old('matieres') ? collect(old('matieres'))->map(fn($m,$i)=>(object)['matiere'=>$m,'coefficient'=>old("coefs.$i"),'note'=>old("notes.$i")]) : ($reg->exists ? $reg->lignes : collect([(object)['matiere'=>'','coefficient'=>'','note'=>'']])); @endphp
@foreach($lignes as $l)<tr><td><input name="matieres[]" class="form-control form-control-sm" value="{{ $l->matiere }}" required></td><td><input name="coefs[]" class="form-control form-control-sm" value="{{ $l->coefficient }}"></td><td><input name="notes[]" class="form-control form-control-sm" value="{{ $l->note }}"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">×</button></td></tr>@endforeach
</tbody></table><div class="mb-3"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="ajout()">+ Ajouter une matière</button></div>
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Total (tel qu'officiel)</label><input name="total_saisi" class="form-control" value="{{ old('total_saisi',$reg->total_saisi) }}"></div>
<div class="col-md-3"><label class="form-label">Moyenne (telle qu'officielle)</label><input name="moyenne_saisie" class="form-control" value="{{ old('moyenne_saisie',$reg->moyenne_saisie) }}"></div>
<div class="col-md-3"><label class="form-label">Résultat officiel</label><select name="resultat_officiel" class="form-select"><option value="">—</option>@foreach(config('registres.resultats') as $k=>$l)<option value="{{ $k }}" @selected(old('resultat_officiel',$reg->resultat_officiel)===$k)>{{ $l }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Réf. de la demande</label><input name="reference_demande" class="form-control" value="{{ old('reference_demande',$reg->reference_demande) }}"></div>
<div class="col-12"><label class="form-label">Observations</label><input name="observations" class="form-control" value="{{ old('observations',$reg->observations) }}"></div>
@if($reg->exists)<div class="col-12"><label class="form-label">Motif de la correction (obligatoire)</label><input name="motif" class="form-control" required value="{{ old('motif') }}"></div>@endif
</div><div class="mt-3"><button class="btn btn-primary">Enregistrer</button></div></form>
<script>function ajout(){document.getElementById('corps').insertAdjacentHTML('beforeend','<tr><td><input name="matieres[]" class="form-control form-control-sm" required></td><td><input name="coefs[]" class="form-control form-control-sm"></td><td><input name="notes[]" class="form-control form-control-sm"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()">×</button></td></tr>')}</script>
@endsection
