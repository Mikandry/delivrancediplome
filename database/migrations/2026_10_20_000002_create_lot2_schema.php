<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jours_feries', function (Blueprint $t) {
            $t->id();
            $t->date('jour')->unique();
            $t->string('libelle', 120);
            $t->timestamps();
        });

        Schema::create('imports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('session_id')->constrained('sessions_examens')->restrictOnDelete();
            $t->unsignedBigInteger('user_id');
            $t->string('fichier_original');
            $t->string('chemin');
            $t->enum('statut', ['apercu', 'termine', 'annule'])->default('apercu');
            $t->unsignedInteger('total')->default(0);
            $t->unsignedInteger('importes')->default(0);
            $t->timestamps();
        });

        Schema::create('import_lignes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
            $t->unsignedInteger('ligne');
            $t->json('donnees');
            $t->json('erreurs')->nullable();
            $t->enum('doublon', ['exact', 'fichier', 'ressemblance'])->nullable();
            $t->index(['import_id', 'ligne']);
        });

        Schema::create('demandes_diplomes', function (Blueprint $t) {
            $t->id();
            $t->string('numero', 20)->unique();
            $t->text('code_suivi');                       // chiffré ; sert au suivi public
            $t->unsignedBigInteger('user_id')->index();   // client demandeur
            $t->foreignId('examen_id')->constrained('examens')->restrictOnDelete();
            $t->foreignId('session_id')->constrained('sessions_examens')->restrictOnDelete();
            $t->unsignedBigInteger('candidat_id')->nullable()->index();
            $t->unsignedBigInteger('diplome_id')->nullable()->index();
            $t->string('nom', 120);
            $t->string('prenoms', 200);
            $t->date('date_naissance');
            $t->string('lieu_naissance', 150);
            $t->string('numero_inscription', 40)->nullable();
            $t->string('telephone', 30)->nullable();
            $t->string('statut', 30)->default('recue')->index();
            $t->timestamp('recu_at');
            $t->timestamp('complet_at')->nullable();       // début du délai
            $t->date('date_prevue')->nullable();
            $t->timestamp('delivre_at')->nullable();
            $t->string('service_recepteur');
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('pieces_dossiers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('demande_id')->constrained('demandes_diplomes')->restrictOnDelete();
            $t->string('type', 30);
            $t->string('nom_original');
            $t->string('chemin');
            $t->string('mime', 100)->nullable();
            $t->unsignedInteger('taille')->default(0);
            $t->unsignedBigInteger('user_id');
            $t->timestamps();
        });

        Schema::create('demande_historique', function (Blueprint $t) {
            $t->id();
            $t->foreignId('demande_id')->constrained('demandes_diplomes')->restrictOnDelete();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('de', 30)->nullable();
            $t->string('vers', 30);
            $t->text('motif')->nullable();
            $t->boolean('visible_client')->default(false);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('notifications_internes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('titre');
            $t->text('message');
            $t->string('lien')->nullable();
            $t->timestamp('lu_at')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['notifications_internes', 'demande_historique', 'pieces_dossiers', 'demandes_diplomes', 'import_lignes', 'imports', 'jours_feries'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
