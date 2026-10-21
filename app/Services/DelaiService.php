<?php
namespace App\Services;

use App\Models\JourFerie;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class DelaiService
{
    /** Ajoute $n jours OUVRÉS (hors samedi, dimanche et jours fériés configurés). Le jour de départ n'est pas compté. */
    public function ajouterJoursOuvres(CarbonInterface $depuis, ?int $n = null): Carbon
    {
        $n ??= (int) config('workflow.delai_jours_ouvres');
        $feries = JourFerie::pluck('jour')->map(fn ($d) => $d->format('Y-m-d'))->flip();
        $d = Carbon::parse($depuis->format('Y-m-d'));
        $compte = 0;
        while ($compte < $n) {
            $d->addDay();
            if ($d->isWeekend() || isset($feries[$d->format('Y-m-d')])) continue;
            $compte++;
        }
        return $d;
    }
}
