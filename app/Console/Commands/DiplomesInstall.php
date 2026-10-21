<?php
namespace App\Console\Commands;

use App\Models\{Examen, User};
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;

class DiplomesInstall extends Command
{
    protected $signature = 'diplomes:install {--email=mikandry7@gmail.com}';
    protected $description = "Initialise les examens et crée le super administrateur (mot de passe temporaire à changer à la 1re connexion).";

    public function handle(): int
    {
        foreach (config('diplomes.examens') as $code => $reg) {
            Examen::firstOrCreate(['code' => $code], ['intitule' => $reg['intitule'], 'actif' => true]);
        }
        $this->info('Examens initialisés : ' . implode(', ', array_keys(config('diplomes.examens'))));

        $email = $this->option('email');
        if (User::where('email', $email)->exists()) {
            $this->warn("Le compte $email existe déjà : aucun changement.");
            return self::SUCCESS;
        }
        $pwd = $this->secret('Mot de passe initial TEMPORAIRE (il sera obligatoirement changé à la 1re connexion)');
        if (! $pwd || strlen($pwd) < 8) {
            $this->error('Mot de passe trop court (8 caractères minimum pour le mot de passe temporaire).');
            return self::FAILURE;
        }
        User::create(['name' => 'Super administrateur', 'email' => $email, 'password' => $pwd, 'role' => 'super_admin', 'actif' => true, 'must_change_password' => true]);
        $this->info("Super administrateur créé : $email (changement de mot de passe obligatoire).");
        return self::SUCCESS;
    }
}
