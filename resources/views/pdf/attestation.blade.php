@php
  \Carbon\Carbon::setLocale('fr');
  $c->loadMissing('diplome');
  $naiss = $c->date_naissance->locale('fr')->translatedFormat('d F Y');
  $prov = $a->type === 'provisoire';
@endphp
<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>{{ $a->numero }}</title>
<style>
 @page { margin: 14mm; } body { font-family: "DejaVu Serif", serif; font-size: 11pt; color: #14213d; line-height: 1.5; }
 .ent { text-align: center; border-bottom: 0.6mm solid #16324f; padding-bottom: 3mm; margin-bottom: 6mm; font-size: 9.5pt; }
 h1 { text-align: center; font-size: 15pt; letter-spacing: 1pt; margin: 6mm 0 2mm; } .num { text-align: center; font-size: 9.5pt; margin-bottom: 8mm; }
 .corps { text-align: justify; margin: 0 6mm; } .nom { text-align:center; font-size: 14pt; font-weight: bold; margin: 5mm 0; }
 .prov { text-align:center; color:#9b1c1c; font-weight:bold; border: 0.5mm solid #9b1c1c; padding: 2mm; margin: 4mm 6mm; }
 .pied { margin-top: 14mm; } .note { font-size: 8.5pt; color:#444; margin-top: 8mm; border-top: 0.2mm solid #999; padding-top: 2mm; }
</style></head><body>
<div class="ent">
 @if(file_exists(public_path('images/logo-ministere.png')))<img src="{{ public_path('images/logo-ministere.png') }}" style="height:18mm"><br>@endif
 <strong>REPOBLIKAN'I MADAGASIKARA</strong><br><em>Fitiavana - Tanindrazana - Fandrosoana</em><br><strong>MINISTÈRE DE L'ÉDUCATION NATIONALE</strong><br>{{ config('diplomes.service') }}</div>
<h1>{{ mb_strtoupper($a->libelleType()) }}</h1>
<div class="num">N° {{ $a->numero }}</div>
@if($prov)<div class="prov">DOCUMENT PROVISOIRE — NE CONSTITUE PAS LE DIPLÔME — résultat en attente d'approbation officielle</div>@endif
<div class="corps">
 Le Directeur des Examens et de la Certification atteste que :
 <div class="nom">{{ mb_strtoupper($c->nom) }} {{ $c->prenoms }}</div>
 né(e) le {{ $naiss }} à {{ $c->lieu_naissance }}, inscrit(e) sous le n° {{ $c->numero_inscription }} au centre « {{ $c->centre_ecrit }} », a présenté l'examen du
 {{ $reg['diplome_court'] ?? $c->examen->code }} (session {{ $c->session->annee }}).
 @if($a->type === 'perte')
  <br><br>Le diplôme n° <strong>{{ $c->diplome->numero_diplome ?? '—' }}</strong>@if($c->diplome?->date_signature), signé le {{ $c->diplome->date_signature->locale('fr')->translatedFormat('d F Y') }},@endif a été établi à son nom. L'intéressé(e) a déclaré l'avoir perdu(e)@if($a->reference) (référence : {{ $a->reference }})@endif.
  <br><br>La présente attestation est délivrée pour servir et valoir ce que de droit. <strong>Elle ne remplace pas le diplôme.</strong>
 @elseif($prov)
  <br><br>Le résultat de l'intéressé(e) est en attente d'approbation officielle. La présente attestation est délivrée à titre <strong>provisoire</strong>@if($a->valide_jusqu), valable jusqu'au <strong>{{ $a->valide_jusqu->format('d/m/Y') }}</strong>@endif, et ne confère aucun droit définitif. Elle sera caduque dès l'établissement du diplôme.
 @else
  <br><br>Le résultat a été officiellement approuvé. La présente attestation est délivrée pour servir et valoir ce que de droit.
 @endif
</div>
<table class="pied" style="width:100%"><tr><td style="width:40mm"><img src="{{ $qr }}" style="width:32mm;height:32mm"></td>
<td style="text-align:right">Fait à {{ config('diplomes.lieu_signature') }}, le {{ $a->emise_at->format('d/m/Y') }}<br><strong>Le Directeur des Examens et de la Certification</strong><br><br><br>{{ config('diplomes.directeur_nom') }}</td></tr></table>
<div class="note">Authenticité : scannez le code QR ou ouvrez {{ $url }}. Statut actuel du document : consultable uniquement en ligne.</div>
</body></html>
