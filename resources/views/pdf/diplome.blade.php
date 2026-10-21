@php
  \Carbon\Carbon::setLocale('fr');
  $nomMaj = mb_strtoupper($c->nom);
  $naissance = $c->date_naissance->locale('fr')->translatedFormat('d F Y');
  $pvDate = $session->pv_date ? $session->pv_date->locale('fr')->translatedFormat('d F Y') : '.....................';
  $dateSign = $d->date_signature ? $d->date_signature->locale('fr')->translatedFormat('d F Y') : '..................';
  $arrete = $session->arrete_reference ?: ($reg['arrete'] ?? '');
@endphp
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><title>Diplôme {{ $d->numero_diplome }}</title>
<style>
 @page { margin: 0; }
 body { margin: 0; font-family: "Times", serif; color: #14213d; font-size: 10.5pt; }
 .abs { position: absolute; }
 .c1 { left: 7mm; top: 7mm; width: 276mm; height: 190mm; border: 1.6mm double #16324f; }
 .c2 { left: 11.5mm; top: 11.5mm; width: 270mm; height: 184mm; border: 0.5mm solid #a9822f; }
 .c3 { left: 13.5mm; top: 13.5mm; width: 266mm; height: 180mm; border: 0.25mm solid #16324f; }
 .coin { width: 5mm; height: 5mm; background: #a9822f; }
 .col-g { left: 19mm; top: 20mm; width: 52mm; text-align: center; }
 .col-d { left: 214mm; top: 20mm; width: 62mm; text-align: center; }
 .centre { left: 78mm; top: 18mm; width: 134mm; text-align: center; }
 .petit { font-size: 8pt; letter-spacing: .3pt; }
 .titre { font-size: 17pt; font-weight: bold; letter-spacing: 1pt; line-height: 1.25; margin: 2mm 0 2mm; color: #16324f; }
 .filet { border-top: 0.4mm solid #a9822f; width: 40mm; margin: 2mm auto; }
 .vu { text-align: left; font-size: 9.5pt; line-height: 1.45; }
 .nom { font-size: 17pt; font-weight: bold; letter-spacing: .6pt; text-align: center; margin: 2mm 0 1mm; color: #000; }
 .val { border-bottom: 0.2mm dotted #555; font-weight: bold; padding: 0 1mm; }
 .ref { text-align: left; font-size: 9pt; line-height: 1.6; margin-top: 8mm; }
 .signature { left: 19mm; top: 118mm; width: 62mm; text-align: center; font-size: 9pt; }
 .qr { left: 19mm; top: 150mm; width: 30mm; font-size: 6.5pt; text-align: center; }
 .fait { left: 168mm; top: 128mm; width: 104mm; text-align: center; font-size: 9.5pt; line-height: 1.4; }
 .pied { left: 19mm; top: 186mm; width: 258mm; font-size: 6.5pt; color: #555; }
 .filigrane { left: 40mm; top: 70mm; width: 220mm; text-align: center; font-size: 80pt; color: #e3e3e3; font-weight: bold; }
 .dup { left: 214mm; top: 52mm; width: 62mm; text-align: center; font-size: 9pt; color: #9b1c1c; font-weight: bold; }
</style></head><body>
@if($specimen)<div class="abs filigrane">SPÉCIMEN</div>@endif

<div class="abs c1"></div><div class="abs c2"></div><div class="abs c3"></div>
<div class="abs coin" style="left:9mm;top:9mm"></div><div class="abs coin" style="left:268mm;top:9mm"></div>
<div class="abs coin" style="left:9mm;top:186mm"></div><div class="abs coin" style="left:268mm;top:186mm"></div>

{{-- Colonne gauche : ministère --}}
<div class="abs col-g">
  @if(file_exists(public_path('images/logo-ministere.png')))<img src="{{ public_path('images/logo-ministere.png') }}" style="height:22mm"><br>@endif
  <div class="petit"><strong>MINISTÈRE DE<br>L'ÉDUCATION NATIONALE</strong></div>
  <div class="petit" style="margin-top:2mm">{{ config('diplomes.service') }}</div>
</div>

{{-- Colonne droite : république + références --}}
<div class="abs col-d">
  <div style="font-size:11pt;font-weight:bold;letter-spacing:.5pt">REPOBLIKAN'I MADAGASIKARA</div>
  <div class="petit"><em>Fitiavana - Tanindrazana - Fandrosoana</em></div>
  <table style="width:100%;margin-top:6mm;font-size:9pt;text-align:left;border-collapse:collapse">
    <tr><td style="width:24mm">N° du diplôme</td><td class="val">{{ $d->numero_diplome }}</td></tr>
    <tr><td>Centre</td><td class="val">{{ $c->centre_ecrit }}</td></tr>
    <tr><td>N° d'inscription</td><td class="val">{{ $c->numero_inscription }}</td></tr>
    @if($c->numero_candidat)<tr><td>N° de candidat</td><td class="val">{{ $c->numero_candidat }}</td></tr>@endif
  </table>
</div>
@if($rang > 1)<div class="abs dup">RÉIMPRESSION AUTORISÉE N° {{ $rang - 1 }}</div>@endif

{{-- Corps central --}}
<div class="abs centre">
  <div class="titre">{{ $reg['intitule'] ?? $c->examen->intitule }}</div>
  <div class="filet"></div>
  <div style="font-weight:bold;font-size:11pt">{{ config('diplomes.ministre_titre') }}</div>
  <div class="vu" style="margin-top:2mm">
    – Vu la Constitution ;<br>
    – Vu l'arrêté {{ $arrete }} portant organisation du {{ $reg['abrev_texte'] ?? '' }} ;<br>
    – Vu le procès-verbal du <span class="val">{{ $pvDate }}</span>@if($session->pv_reference) (réf. {{ $session->pv_reference }})@endif, de la {{ $reg['cycle'] ?? "Commission d'Examen" }} siégeant à
    <span class="val">{{ $c->lycee_siegeant }}</span>, pour la session de <span class="val">{{ $session->annee }}</span>, atteste que :
  </div>
  <div class="nom">{{ $nomMaj }}</div>
  <div style="font-size:14pt;font-weight:bold;margin-bottom:2mm">{{ $c->prenoms }}</div>
  <div class="vu">né(e) le <span class="val">{{ $naissance }}</span> à <span class="val">{{ $c->lieu_naissance }}</span>
    a été jugé(e) apte à obtenir le {{ $reg['diplome_court'] ?? $c->examen->code }}@if($c->mention), avec la mention <strong>{{ $c->mention }}</strong>@endif, dans les conditions déterminées par les textes susvisés.<br>
    <strong>Il lui est délivré {{ $reg['delivre'] ?? 'le présent diplôme' }} pour en jouir avec tous les droits et prérogatives qui y sont attachés.</strong>
  </div>
</div>

{{-- Signatures --}}
<div class="abs signature"><em>Signature de l'impétrant(e)</em></div>
<div class="abs fait">
  Fait à {{ $d->lieu_signature }}, le <span class="val">{{ $dateSign }}</span><br>
  P. Le Ministre de l'Éducation Nationale<br>Par délégation,<br>Le Secrétaire Général,<br>Par subdélégation,<br>
  <strong>Le Directeur des Examens et de la Certification</strong><br><br><br>
  @if($directeur)<strong>{{ $directeur }}</strong>@endif
</div>

{{-- QR de vérification --}}
<div class="abs qr"><img src="{{ $qr }}" style="width:26mm;height:26mm"><br>Vérifier l'authenticité<br>{{ substr($d->token_verification, 0, 8) }}…</div>

<div class="abs pied">Vérification : {{ $url }} — Imprimé le {{ now()->format('d/m/Y H:i') }}@if($rang > 1) — exemplaire n°{{ $rang }}@endif</div>
</body></html>
