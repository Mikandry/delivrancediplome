<?php
namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class DiplomesRestaurer extends Command
{
    protected $signature = 'diplomes:restaurer {fichier : nom du fichier dans storage/app/private/sauvegardes}';
    protected $description = 'Restaure la base depuis une sauvegarde (écrase les données actuelles).';
    public function handle(BackupService $b): int
    {
        $f = $b->dossier() . '/' . basename($this->argument('fichier'));
        if (! is_file($f)) { $this->error("Introuvable : $f"); return self::FAILURE; }
        $db = config('database.connections.' . config('database.default') . '.database');
        if ($this->ask("ATTENTION : la base « $db » va être écrasée. Tapez son nom pour confirmer") !== $db) { $this->warn('Annulé.'); return self::FAILURE; }
        try { $b->restaurer($f); $this->info('Restauration terminée.'); return self::SUCCESS; }
        catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}
