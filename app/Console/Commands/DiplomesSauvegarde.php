<?php
namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class DiplomesSauvegarde extends Command
{
    protected $signature = 'diplomes:sauvegarde';
    protected $description = 'Sauvegarde compressée de la base (30 dernières conservées).';
    public function handle(BackupService $b): int
    {
        try { $this->info('Sauvegarde créée : ' . $b->creer()); return self::SUCCESS; }
        catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}
