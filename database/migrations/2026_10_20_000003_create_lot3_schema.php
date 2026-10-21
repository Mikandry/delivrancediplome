<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attestations', function (Blueprint $t) {
            $t->id();
            $t->string('numero', 30)->unique();
            $t->string('type', 20)->index();
            $t->foreignId('candidat_id')->constrained('candidats')->restrictOnDelete();
            $t->unsignedBigInteger('diplome_id')->nullable();
            $t->enum('statut', ['valide', 'revoque', 'remplace'])->default('valide');
            $t->text('reference')->nullable();          // ex. référence de la déclaration de perte
            $t->string('token_verification', 64)->unique();
            $t->date('valide_jusqu')->nullable();
            $t->unsignedBigInteger('emise_par');
            $t->timestamp('emise_at');
            $t->text('motif_revocation')->nullable();
            $t->timestamps();
        });

        Schema::create('registres_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('candidat_id')->constrained('candidats')->restrictOnDelete();
            $t->string('reference_demande')->nullable();
            $t->string('resultat_officiel', 20)->nullable();
            $t->string('total_saisi', 20)->nullable();
            $t->string('moyenne_saisie', 20)->nullable();
            $t->text('observations')->nullable();
            $t->string('token_verification', 64)->unique();
            $t->unsignedBigInteger('saisi_par');
            $t->date('date_etablissement');
            $t->timestamps();
        });

        Schema::create('lignes_registres_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('registre_id')->constrained('registres_notes')->cascadeOnDelete();
            $t->unsignedSmallInteger('ordre')->default(0);
            $t->string('matiere', 120);
            $t->decimal('coefficient', 4, 1)->nullable();
            $t->decimal('note', 5, 2)->nullable();
        });

        Schema::create('organismes_verification', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('email')->nullable();
            $t->char('cle_hash', 64)->unique();         // la clé elle-même n'est jamais stockée
            $t->string('cle_prefixe', 12);
            $t->boolean('actif')->default(true);
            $t->unsignedInteger('limite_heure')->default(500);
            $t->timestamp('dernier_usage_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['organismes_verification', 'lignes_registres_notes', 'registres_notes', 'attestations'] as $t) Schema::dropIfExists($t);
    }
};
