@php \Carbon\Carbon::setLocale('fr'); @endphp
<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Registre de notes</title>
<style>
 @page { margin: 14mm; } body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #14213d; }
 .ent { text-align: center; border-bottom: 0.6mm solid #16324f; padding-bottom: 3mm; margin-bottom: 5mm; font-size: 9pt; }
 h1 { text-align:center; font-size: 14pt; margin: 3mm 0 5mm; }
 table.n { width:100%; border-collapse: collapse; margin-top: 4mm; } table.n th, table.n td { border: 0.2mm solid #777; padding: 1.6mm 2mm; } table.n th { background:#eef2f7; text-align:left; }
 table.i td { padding: 1mm 2mm; }
</style></head><body>
<div class="ent">
 @if(file_exists(public_path('images/logo-ministere.png')))<img src="{{ public_path('images/logo-ministere.png') }}" style="height:16mm"><br>@endif
 <strong>REPOBLIKAN'I MADAGASIKARA</strong> — <em>Fitiavana - Tanindrazana - Fandrosoana</em><br><strong>MINISTÈRE DE L'ÉDUCATION NATIONALE</strong><br>{{ config('diplomes.service') }}</div>
<h1>REGISTRE DE NOTES — {{ $c->examen->code }} — SESSION {{ $c->session->annee }}</h1>
<table class="i"><tr><td><strong>Nom et prénoms</strong></td><td>{{ mb_strtoupper($c->nom) }} {{ $c->prenoms }}</td></tr>
<tr><td><strong>Né(e) le</strong></td><td>{{ $c->date_naissance->format('d/m/Y') }} à {{ $c->lieu_naissance }}</td></tr>
<tr><td><strong>N° de candidat / d'inscription</strong></td><td>{{ $c->numero_candidat ?: '—' }} / {{ $c->numero_inscription }}</td></tr>
@if($reg->reference_demande)<tr><td><strong>Référence de la demande</strong></td><td>{{ $reg->reference_demande }}</td></tr>@endif</table>
<table class="n"><thead><tr><th>Matière</th><th style="width:22mm">Coef.</th><th style="width:22mm">Note</th></tr></thead><tbody>
@foreach($reg->lignes as $l)<tr><td>{{ $l->matiere }}</td><td>{{ $l->coefficient !== null ? rtrim(rtrim((string)$l->coefficient,'0'),'.') : '' }}</td><td>{{ $l->note !== null ? $l->note : '' }}</td></tr>@endforeach</tbody></table>
<table class="i" style="margin-top:4mm"><tr><td><strong>Total</strong></td><td>{{ $reg->total_saisi ?: '—' }}</td><td><strong>Moyenne</strong></td><td>{{ $reg->moyenne_saisie ?: '—' }}</td><td><strong>Résultat officiel</strong></td><td>{{ $rv[$reg->resultat_officiel] ?? 'Non communiqué' }}</td></tr></table>
@if($reg->observations)<p><strong>Observations :</strong> {{ $reg->observations }}</p>@endif
<table style="width:100%;margin-top:8mm"><tr><td style="width:36mm"><img src="{{ $qr }}" style="width:30mm;height:30mm"></td>
<td style="text-align:right;font-size:9.5pt">Établi à {{ config('diplomes.lieu_signature') }}, le {{ $reg->date_etablissement->format('d/m/Y') }}<br><strong>Le Directeur des Examens et de la Certification</strong><br><br><br>{{ config('diplomes.directeur_nom') }}</td></tr></table>
<p style="font-size:8pt;color:#555">Vérification : {{ $url }}</p>
</body></html>
