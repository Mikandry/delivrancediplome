@extends('layouts.app')
@section('titre', 'Nouvelle demande de délivrance de diplôme')
@section('contenu')
<form method="post" action="{{ route('demandes.store') }}" enctype="multipart/form-data" class="card card-body">@csrf
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Diplôme et session</label><select name="session_id" class="form-select" required>@foreach($sessions as $s)<option value="{{ $s->id }}" @selected(old('session_id')==$s->id)>{{ $s->examen->code }} — session {{ $s->annee }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Nom</label><input name="nom" value="{{ old('nom') }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Prénoms</label><input name="prenoms" value="{{ old('prenoms') }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Date de naissance</label><input type="date" name="date_naissance" value="{{ old('date_naissance') }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Lieu de naissance</label><input name="lieu_naissance" value="{{ old('lieu_naissance') }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">N° d'inscription (si connu)</label><input name="numero_inscription" value="{{ old('numero_inscription') }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Téléphone</label><input name="telephone" value="{{ old('telephone') }}" class="form-control"></div>
<div class="col-12"><hr><h6>Pièces justificatives (PDF, JPG ou PNG, 5 Mo max chacune)</h6></div>
@foreach(config('workflow.pieces') as $type=>$def)<div class="col-md-4"><label class="form-label">{{ $def['label'] }} @if($def['obligatoire'])<span class="text-danger">*</span>@endif</label><input type="file" name="piece_{{ $type }}" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>@endforeach
</div><p class="small text-muted mt-3">Le délai de {{ config('workflow.delai_jours_ouvres') }} jours ouvrés ne démarre qu'à la réception d'un dossier complet. Vous pourrez ajouter les pièces manquantes ensuite.</p>
<div><button class="btn btn-primary">Envoyer la demande</button></div></form>
@endsection
