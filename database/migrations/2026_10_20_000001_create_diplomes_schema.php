<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 30)->default('client')->index();
            $t->boolean('actif')->default(true);
            $t->boolean('must_change_password')->default(true);
            $t->string('telephone', 30)->nullable();
        });

        Schema::create('examens', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();      // BEPC, CAE.EP, CAP.EP
            $t->string('intitule');
            $t->boolean('actif')->default(true);
            $t->timestamps();
        });

        Schema::create('sessions_examens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('examen_id')->constrained('examens')->restrictOnDelete();
            $t->unsignedSmallInteger('annee');
            $t->string('libelle', 100)->nullable();
            $t->date('pv_date')->nullable();              // date du procès-verbal de délivrance
            $t->string('pv_reference', 100)->nullable();
            $t->string('arrete_reference')->nullable();   // surcharge de l'arrêté par défaut
            $t->boolean('verrouillee')->default(false);   // liste validée par l'administrateur
            $t->timestamp('verrouillee_at')->nullable();
            $t->unsignedBigInteger('verrouillee_par')->nullable();
            $t->timestamps();
            $t->unique(['examen_id', 'annee']);
        });

        Schema::create('candidats', function (Blueprint $t) {
            $t->id();
            $t->foreignId('examen_id')->constrained('examens')->restrictOnDelete();
            $t->foreignId('session_id')->constrained('sessions_examens')->restrictOnDelete();
            $t->string('nom', 120);
            $t->string('prenoms', 200);
            $t->string('nom_normalise', 330)->index();    // recherche/doublons : sans accents, minuscules
            $t->date('date_naissance');
            $t->string('lieu_naissance', 150);
            $t->enum('sexe', ['M', 'F'])->nullable();
            $t->string('nationalite', 60)->default('Malagasy');
            $t->string('numero_candidat', 40)->nullable();
            $t->string('numero_inscription', 40);
            $t->string('centre_ecrit', 150);              // nom du centre d'écrit
            $t->string('lycee_siegeant', 150);            // lycée d'accueil / commission siégeant à
            $t->string('mention', 40)->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['session_id', 'numero_inscription']);
        });

        Schema::create('diplomes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('candidat_id')->constrained('candidats')->restrictOnDelete();
            $t->string('numero_diplome', 40)->unique();
            $t->enum('statut', ['brouillon', 'valide_pour_impression', 'imprime', 'delivre', 'revoque', 'remplace'])->default('brouillon')->index();
            $t->timestamp('date_signature')->nullable();  // posée automatiquement par la validation du secrétaire
            $t->string('lieu_signature', 80)->default('Antananarivo');
            $t->unsignedBigInteger('signe_par')->nullable();
            $t->string('token_verification', 64)->unique(); // contenu du QR code, aucune donnée personnelle
            $t->unsignedSmallInteger('nb_impressions')->default(0);
            $t->unsignedBigInteger('cree_par')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique('candidat_id');
        });

        Schema::create('autorisations_reimpression', function (Blueprint $t) {
            $t->id();
            $t->foreignId('diplome_id')->constrained('diplomes')->restrictOnDelete();
            $t->text('motif');
            $t->unsignedBigInteger('autorise_par');
            $t->timestamp('utilisee_at')->nullable();
            $t->timestamps();
        });

        Schema::create('impressions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('diplome_id')->constrained('diplomes')->restrictOnDelete();
            $t->unsignedSmallInteger('rang');             // 1 = original, 2+ = réimpression autorisée
            $t->unsignedBigInteger('user_id');
            $t->foreignId('autorisation_id')->nullable()->constrained('autorisations_reimpression')->restrictOnDelete();
            $t->string('ip', 45)->nullable();
            $t->timestamp('imprime_at')->useCurrent();
            $t->unique(['diplome_id', 'rang']);           // verrou en base contre la double impression
        });

        Schema::create('journaux_audit', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('action', 60)->index();
            $t->string('entite', 60)->nullable();
            $t->unsignedBigInteger('entite_id')->nullable();
            $t->text('motif')->nullable();
            $t->json('anciennes')->nullable();
            $t->json('nouvelles')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            // Journal d'audit en insertion seule.
            DB::unprepared("CREATE TRIGGER audit_no_update BEFORE UPDATE ON journaux_audit FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Journal d audit non modifiable'");
            DB::unprepared("CREATE TRIGGER audit_no_delete BEFORE DELETE ON journaux_audit FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Journal d audit non supprimable'");
            // Une réimpression exige une autorisation non encore utilisée ; l'original exige un diplôme signé.
            DB::unprepared("CREATE TRIGGER impressions_garde BEFORE INSERT ON impressions FOR EACH ROW
BEGIN
  IF NEW.rang = 1 AND (SELECT statut FROM diplomes WHERE id = NEW.diplome_id) <> 'valide_pour_impression' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Diplome non valide pour impression';
  END IF;
  IF NEW.rang > 1 AND NEW.autorisation_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reimpression sans autorisation';
  END IF;
END");
            // Un diplôme signé ne peut plus changer de candidat ni de numéro.
            DB::unprepared("CREATE TRIGGER diplomes_fige BEFORE UPDATE ON diplomes FOR EACH ROW
BEGIN
  IF OLD.date_signature IS NOT NULL AND (NEW.date_signature <=> OLD.date_signature) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Date de signature non modifiable';
  END IF;
  IF OLD.statut <> 'brouillon' AND (NEW.numero_diplome <> OLD.numero_diplome OR NEW.candidat_id <> OLD.candidat_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Donnees officielles non modifiables';
  END IF;
END");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            foreach (['audit_no_update', 'audit_no_delete', 'impressions_garde', 'diplomes_fige'] as $tr) {
                DB::unprepared("DROP TRIGGER IF EXISTS $tr");
            }
        }
        Schema::dropIfExists('journaux_audit');
        Schema::dropIfExists('impressions');
        Schema::dropIfExists('autorisations_reimpression');
        Schema::dropIfExists('diplomes');
        Schema::dropIfExists('candidats');
        Schema::dropIfExists('sessions_examens');
        Schema::dropIfExists('examens');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['role', 'actif', 'must_change_password', 'telephone']);
        });
    }
};
