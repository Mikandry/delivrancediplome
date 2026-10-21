@extends('layouts.app')
@section('titre', 'Attestations')
@section('contenu')
@if(auth()->user()->hasPermission('attestations.emettre'))
<div class="card card-body mb-4"><h6>Établir une attestation</h6>
<form class="row g-2"><div class="col-md-6"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher le candidat : nom ou n° d'inscription"></div><div class="col-auto"><button class="btn btn-primary">Rechercher</button></div></form>
@foreach($cands as $c)<div class="d-flex justify-content-between border-bottom py-1 small"><span>{{ $c->nomComplet() }} — {{ $c->examen->code }} {{ $c->session->annee }} — n° {{ $c->numero_inscription }}</span><a class="btn btn-sm btn-outline-primary" href="{{ route('attestations.create',$c) }}">Choisir</a></div>@endforeach
@if(request('q') && $cands->isEmpty())<div class="small text-muted mt-2">Aucun candidat trouvé.</div>@endif</div>@endif
<table class="table table-sm bg-white align-middle"><thead><tr><th>N°</th><th>Type</th><th>Titulaire</th><th>Émise le</th><th>État</th><th></th></tr></thead><tbody>
@forelse($attestations as $a)<tr><td>{{ $a->numero }}</td><td>{{ $a->libelleType() }}</td><td>{{ $a->candidat->nomComplet() }}</td><td>{{ $a->emise_at->format('d/m/Y') }}</td>
<td>{{ ucfirst($a->statut) }}@if($a->valide_jusqu) <small class="text-muted">(jusqu'au {{ $a->valide_jusqu->format('d/m/Y') }})</small>@endif</td>
<td class="d-flex gap-1"><a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('attestations.pdf',$a) }}">PDF</a>
@if($a->statut==='valide' && auth()->user()->hasPermission('attestations.revoquer'))<form method="post" action="{{ route('attestations.revoquer',$a) }}" class="d-flex gap-1" onsubmit="return confirm('Révoquer cette attestation ?')">@csrf<input name="motif" class="form-control form-control-sm" placeholder="Motif" required><button class="btn btn-sm btn-outline-danger">Révoquer</button></form>@endif</td></tr>
@empty<tr><td colspan="6" class="text-center text-muted">Aucune attestation.</td></tr>@endforelse</tbody></table>{{ $attestations->links() }}
@endsection
