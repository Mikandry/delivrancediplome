@extends('layouts.app')
@section('titre', "Sessions d'examen")
@section('contenu')
<div class="card card-body mb-4"><h6>Nouvelle session</h6>
<form method="post" action="{{ route('sessions.store') }}" class="row g-2">@csrf
<div class="col-md-2"><select name="examen_id" class="form-select">@foreach($examens as $e)<option value="{{ $e->id }}">{{ $e->code }}</option>@endforeach</select></div>
<div class="col-md-1"><input name="annee" type="number" class="form-control" placeholder="Année" required></div>
<div class="col-md-2"><input name="libelle" class="form-control" placeholder="Libellé"></div>
<div class="col-md-2"><input type="date" name="pv_date" class="form-control" title="Date du PV de délivrance"></div>
<div class="col-md-2"><input name="pv_reference" class="form-control" placeholder="Réf. du PV"></div>
<div class="col-md-2"><input name="arrete_reference" class="form-control" placeholder="Arrêté (si différent)"></div>
<div class="col-md-1"><button class="btn btn-primary w-100">Créer</button></div></form></div>
<table class="table table-sm bg-white align-middle"><thead><tr><th>Examen</th><th>Année</th><th>Candidats</th><th>PV de délivrance</th><th>État de la liste</th><th></th></tr></thead><tbody>
@foreach($sessions as $s)<tr><td>{{ $s->examen->code }}</td><td>{{ $s->annee }}</td><td>{{ $s->candidats_count }}</td>
<td>@if($s->verrouillee){{ $s->pv_date?->format('d/m/Y') ?? '—' }} {{ $s->pv_reference }}@else
<form method="post" action="{{ route('sessions.update',$s) }}" class="d-flex gap-1">@csrf @method('PUT')<input type="date" name="pv_date" value="{{ $s->pv_date?->format('Y-m-d') }}" class="form-control form-control-sm"><input name="pv_reference" value="{{ $s->pv_reference }}" class="form-control form-control-sm" placeholder="Réf."><button class="btn btn-sm btn-outline-primary">OK</button></form>@endif</td>
<td>{!! $s->verrouillee ? '<span class="badge text-bg-success">🔒 Validée le '.$s->verrouillee_at?->format('d/m/Y').'</span>' : '<span class="badge text-bg-warning">Ouverte</span>' !!}</td>
<td>@if(!$s->verrouillee && auth()->user()->hasPermission('sessions.verrouiller'))<form method="post" action="{{ route('sessions.verrouiller',$s) }}" onsubmit="return confirm('Valider et verrouiller cette liste ? Les saisisseurs ne pourront plus la modifier.')">@csrf<button class="btn btn-sm btn-success">Valider et verrouiller</button></form>
@elseif($s->verrouillee && auth()->user()->hasPermission('sessions.deverrouiller'))<form method="post" action="{{ route('sessions.deverrouiller',$s) }}" class="d-flex gap-1" onsubmit="return confirm('Déverrouillage exceptionnel, journalisé. Continuer ?')">@csrf<input name="motif" class="form-control form-control-sm" placeholder="Motif (10 car. min.)" required><button class="btn btn-sm btn-outline-danger">Déverrouiller</button></form>@endif</td></tr>@endforeach
</tbody></table>
@endsection
