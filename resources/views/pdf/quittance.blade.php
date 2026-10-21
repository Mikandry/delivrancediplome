<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Quittance {{ $d->numero }}</title>
<style>
 @page { margin: 14mm; } body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #14213d; }
 .ent { text-align: center; border-bottom: 0.6mm solid #16324f; padding-bottom: 3mm; margin-bottom: 5mm; }
 h1 { font-size: 15pt; text-align: center; margin: 4mm 0; letter-spacing: 1pt; }
 table.t { width: 100%; border-collapse: collapse; } table.t td { border: 0.2mm solid #888; padding: 2mm; vertical-align: top; } table.t td.l { width: 38%; background: #f1f4f8; font-weight: bold; }
 .note { font-size: 8.5pt; color: #444; margin-top: 4mm; border: 0.2mm dashed #888; padding: 2mm; }
</style></head><body>
<div class="ent">
 @if(file_exists(public_path('images/logo-ministere.png')))<img src="{{ public_path('images/logo-ministere.png') }}" style="height:18mm"><br>@endif
 <strong>REPOBLIKAN'I MADAGASIKARA</strong><br><small><em>Fitiavana - Tanindrazana - Fandrosoana</em></small><br>
 <strong>MINISTÈRE DE L'ÉDUCATION NATIONALE</strong><br>{{ config('diplomes.service') }}</div>
<h1>QUITTANCE DE RÉCEPTION</h1>
<table class="t">
<tr><td class="l">N° de demande</td><td><strong>{{ $d->numero }}</strong></td></tr>
<tr><td class="l">Demandeur</td><td>{{ $d->nomComplet() }}</td></tr>
<tr><td class="l">Né(e) le</td><td>{{ $d->date_naissance->format('d/m/Y') }} à {{ $d->lieu_naissance }}</td></tr>
<tr><td class="l">Diplôme demandé</td><td>{{ $d->examen->code }} — session {{ $d->session->annee }}</td></tr>
<tr><td class="l">Date et heure de réception</td><td>{{ $d->recu_at->format('d/m/Y à H:i') }}</td></tr>
<tr><td class="l">Pièces déposées</td><td>@forelse($d->pieces as $p)• {{ $p->libelle() }}<br>@empty Aucune pièce déposée @endforelse
 @if($d->piecesManquantes())<br><strong>Pièces manquantes :</strong> {{ implode(', ', $d->piecesManquantes()) }}@endif</td></tr>
<tr><td class="l">Service récepteur</td><td>{{ $d->service_recepteur }}</td></tr>
<tr><td class="l">Date prévisionnelle de délivrance</td><td>@if($d->date_prevue && $d->estComplete()){{ $d->date_prevue->format('d/m/Y') }}@else Non annoncée : dossier incomplet @endif</td></tr>
</table>
<table style="width:100%;margin-top:6mm"><tr><td style="width:40mm"><img src="{{ $qr }}" style="width:34mm;height:34mm"></td>
<td style="font-size:9pt">Scannez ce code pour suivre l'avancement de votre demande. Il vous sera demandé le <strong>code de suivi</strong> remis lors de l'enregistrement de la demande.<br><br>Édité le {{ now()->format('d/m/Y à H:i') }}</td></tr></table>
<div class="note"><strong>Mention :</strong> la date de délivrance est <strong>prévisionnelle</strong>. Le délai normal est de {{ $delaiJours }} jours ouvrés (hors samedis, dimanches et jours fériés) à compter de la réception d'un dossier complet ; il peut être prolongé si le dossier doit être corrigé. Cette quittance ne vaut pas diplôme.</div>
</body></html>
