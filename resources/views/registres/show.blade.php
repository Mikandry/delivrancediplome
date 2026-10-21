@extends('layouts.app')
@section('titre', 'Registre de notes n°' . $reg->id)
@section('contenu')
<div class="card card-body mb-3"><strong>{{ $reg->candidat->nomComplet() }}</strong><div class="small text-muted">{{ $reg->candidat->examen->code }} {{ $reg->candidat->session->annee }} — saisi par {{ $reg->saisisseur->name ?? '—' }} le {{ $reg->date_etablissement->format('d/m/Y') }}</div>
<div class="mt-2"><a class="btn btn-primary btn-sm" target="_blank" href="{{ route('registres.pdf',$reg) }}">Générer le PDF</a> <a class="btn btn-outline-secondary btn-sm" href="{{ route('registres.edit',$reg) }}">Corriger</a></div></div>
<table class="table table-sm bg-white" style="max-width:560px"><thead><tr><th>Matière</th><th>Coef.</th><th>Note</th></tr></thead><tbody>@foreach($reg->lignes as $l)<tr><td>{{ $l->matiere }}</td><td>{{ $l->coefficient !== null ? rtrim(rtrim((string)$l->coefficient,'0'),'.') : '' }}</td><td>{{ $l->note !== null ? $l->note : '' }}</td></tr>@endforeach</tbody></table>
<div class="small">Total : {{ $reg->total_saisi ?: '—' }} · Moyenne : {{ $reg->moyenne_saisie ?: '—' }} · Résultat : {{ config('registres.resultats')[$reg->resultat_officiel] ?? '—' }}</div>
@endsection
