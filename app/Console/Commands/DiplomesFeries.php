<?php
namespace App\Console\Commands;

use App\Models\JourFerie;
use Illuminate\Console\Command;

class DiplomesFeries extends Command
{
    protected $signature = 'diplomes:feries {annee}';
    protected $description = 'Ajoute les jours fériés à date FIXE de Madagascar. Les fêtes mobiles (Lundi de Pâques, Ascension, Pentecôte) et décrets exceptionnels sont à ajouter à la main.';

    public function handle(): int
    {
        $a = (int) $this->argument('annee');
        $fixes = ['01-01' => "Jour de l'An", '03-29' => 'Commémoration des événements de 1947', '05-01' => 'Fête du Travail', '06-26' => "Fête de l'Indépendance",
            '08-15' => 'Assomption', '11-01' => 'Toussaint', '12-25' => 'Noël'];
        foreach ($fixes as $md => $lib) JourFerie::firstOrCreate(['jour' => "$a-$md"], ['libelle' => $lib]);
        $this->info("Jours fériés fixes $a ajoutés. VÉRIFIEZ la liste officielle et ajoutez les fêtes mobiles depuis l'écran « Jours fériés ».");
        return self::SUCCESS;
    }
}
