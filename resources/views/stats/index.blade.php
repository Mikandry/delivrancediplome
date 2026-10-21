@extends('layouts.app')
@section('titre', 'Statistiques')
@section('contenu')
<form class="row g-2 mb-3">
<div class="col-md-2"><input type="date" name="du" value="{{ $f['du'] ?? '' }}" class="form-control" title="Du"></div>
<div class="col-md-2"><input type="date" name="au" value="{{ $f['au'] ?? '' }}" class="form-control" title="Au"></div>
<div class="col-md-2"><select name="examen_id" class="form-select"><option value="">Tous examens</option>@foreach($examens as $e)<option value="{{ $e->id }}" @selected(($f['examen_id'] ?? '')==$e->id)>{{ $e->code }}</option>@endforeach</select></div>
<div class="col-md-2"><select name="session_id" class="form-select"><option value="">Toutes sessions</option>@foreach($sessions as $x)<option value="{{ $x->id }}" @selected(($f['session_id'] ?? '')==$x->id)>{{ $x->examen->code }} {{ $x->annee }}</option>@endforeach</select></div>
<div class="col-md-2"><select name="statut" class="form-select"><option value="">Tous statuts</option>@foreach(config('workflow.statuts') as $k=>$l)<option value="{{ $k }}" @selected(($f['statut'] ?? '')==$k)>{{ $l }}</option>@endforeach</select></div>
<div class="col-auto"><button class="btn btn-primary">Filtrer</button></div></form>
<div class="row g-3 mb-3">@foreach([['Demandes',$s['total']],['Délai moyen (jours, réception → impression)',$s['delai_moyen_jours'] ?? '—'],['Hors délai prévu',$s['hors_delai']],['Retours en correction',$s['retours']],['Diplômes validés',$s['diplomes_valides']],['Diplômes imprimés',$s['diplomes_imprimes']],['Réimpressions autorisées',$s['reimpressions']],['Attestations',$s['attestations']->sum()]] as [$l,$n])
<div class="col-md-3"><div class="card card-body"><div class="small text-muted">{{ $l }}</div><div class="fs-3">{{ $n }}</div></div></div>@endforeach</div>
<div class="row g-3"><div class="col-lg-6"><div class="card card-body"><h6>Demandes par statut</h6><canvas id="gr" height="140"></canvas></div></div>
<div class="col-lg-6"><div class="card card-body"><h6>Par examen</h6>@forelse($s['par_examen'] as $k=>$n)<div class="d-flex justify-content-between"><span>{{ $k }}</span><strong>{{ $n }}</strong></div>@empty<span class="text-muted small">—</span>@endforelse
<h6 class="mt-3">Par session</h6>@forelse($s['par_session'] as $k=>$n)<div class="d-flex justify-content-between"><span>{{ $k }}</span><strong>{{ $n }}</strong></div>@empty<span class="text-muted small">—</span>@endforelse
<h6 class="mt-3">Attestations par type</h6>@forelse($s['attestations'] as $k=>$n)<div class="d-flex justify-content-between"><span>{{ config('attestations.types')[$k] ?? $k }}</span><strong>{{ $n }}</strong></div>@empty<span class="text-muted small">—</span>@endforelse</div></div>
<div class="col-lg-6"><div class="card card-body"><h6>Opérations par agent</h6><table class="table table-sm mb-0">@forelse($s['par_agent'] as $a)<tr><td>{{ $a->name }}</td><td class="text-muted">{{ config('permissions.roles')[$a->role] ?? $a->role }}</td><td class="text-end">{{ $a->n }}</td></tr>@empty<tr><td class="text-muted">—</td></tr>@endforelse</table></div></div>
<div class="col-lg-6"><div class="card card-body"><h6>Motifs de retour en correction</h6><table class="table table-sm mb-0">@forelse($s['motifs'] as $m)<tr><td>{{ $m->motif }}</td><td class="text-end">{{ $m->n }}</td></tr>@empty<tr><td class="text-muted">—</td></tr>@endforelse</table></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>new Chart(document.getElementById('gr'),{type:'bar',data:{labels:{!! json_encode($s['par_statut']->keys()->map(fn($k)=>config('workflow.statuts')[$k] ?? $k)->values(), JSON_UNESCAPED_UNICODE) !!},datasets:[{label:'Dossiers',data:{!! json_encode($s['par_statut']->values()) !!},backgroundColor:'#24507c'}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});</script>
@endsection
