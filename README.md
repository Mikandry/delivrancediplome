# Plateforme de gestion et de délivrance des diplômes — Lot 1 (socle + diplôme)

Couche à superposer sur un **Laravel 12 neuf** (PHP 8.3, MySQL 8 / MariaDB 10.6+, extensions `gd`, `mbstring`, `pdo_mysql`, `zip`).
PDO est utilisé normalement (Eloquent, migrations).

## Installation locale
```bash
composer create-project laravel/laravel diplomes && cd diplomes
composer require barryvdh/laravel-dompdf endroid/qr-code:^6.0 phpoffice/phpspreadsheet
# copier le contenu de ce dossier par-dessus le projet (app/, config/, database/, resources/, routes/, tests/)
rm database/migrations/*_create_cache_table.php 2>/dev/null   # facultatif
cp .env.example .env && php artisan key:generate
```
Dans `.env` :
```
APP_NAME="Diplomes" APP_LOCALE=fr APP_TIMEZONE=Indian/Antananarivo APP_URL=http://localhost:8000
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=diplomes DB_USERNAME=... DB_PASSWORD=...
SESSION_DRIVER=file CACHE_STORE=file
DIPLOMES_DIRECTEUR_NOM="Nom du Directeur"
```
Puis :
```bash
mysql -e "CREATE DATABASE diplomes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate
php artisan diplomes:install      # saisir le mot de passe TEMPORAIRE quand demandé
php artisan diplomes:demo         # facultatif : données FICTIVES
php artisan test                  # tests (base MySQL de test recommandée : les triggers n'existent pas sous SQLite)
php artisan serve
```
Connexion : `mikandry7@gmail.com` + mot de passe temporaire → changement obligatoire (12+ car.).
Placer le logo officiel dans `public/images/logo-ministere.png` (optionnel, absent du ZIP).

## Réalisé / non réalisé
Réalisé (non testé, voir ci-dessous) : authentification, rôles, comptes, audit, candidats avec corrections motivées et verrouillage, diplômes, validation avec date de signature automatique, impression unique sous verrou + triggers MySQL, réimpression par super admin, PDF avec QR, page publique de vérification.
**Non réalisé (lots 2 et 3)** : import Excel/CSV, demandes clients et workflow complet, quittances, attestations, registres de notes, jours fériés, vérification en masse des organismes, statistiques avancées, sauvegardes, guides détaillés.
**Intitulés officiels CAE.EP et CAP.EP et leurs arrêtés : à renseigner dans `config/diplomes.php`.**

## Limite importante
Ce code a été écrit sans PHP ni Composer disponibles : **aucun test n'a été exécuté, aucune syntaxe vérifiée**. Attendez-vous à de petites corrections au premier lancement. Bootstrap est chargé par CDN (à héberger localement en production).

---
# Lot 2 (cœur métier) — ajouts

À copier par-dessus le lot 1, puis `php artisan migrate` (migration `2026_10_20_000002`) et, pour les jours fériés fixes : `php artisan diplomes:feries 2026`.

## Contenu
- **Sessions** (`/sessions`) : création, PV de délivrance (date et référence imprimés sur le diplôme), validation et **verrouillage** de la liste par l'administrateur, **déverrouillage exceptionnel** avec motif journalisé.
- **Import Excel/CSV** (`/imports`) : modèle téléchargeable (xlsx/csv, ligne d'exemple fictive), analyse, **aperçu** avec erreurs, doublons exacts et ressemblances, puis confirmation. Rien n'est écrit dans `candidats` avant confirmation. Une ressemblance n'est jamais importée sans case cochée.
- **Demandes** : le client crée un compte (`/inscription`), dépose sa demande et ses pièces (stockées hors accès public, téléchargement contrôlé), reçoit un n° unique et un **code de suivi secret**. Le personnel associe la demande à un candidat de la liste officielle (choix humain).
- **Workflow** (`config/workflow.php`) : table de transitions avec rôle requis et motif obligatoire ; toute transition hors table est refusée côté serveur ; historique conservé ; notifications internes. La validation du secrétaire crée le diplôme et pose la date de signature ; la 1re impression fait passer la demande à « Imprimé » automatiquement.
- **Délai** : 5 jours ouvrés après un dossier complet (hors week-ends et `jours_feries`), règle de retour en correction configurable (`recalcul` ou `ignorer`). Aucune date annoncée tant que le dossier est incomplet.
- **Quittance PDF** avec QR de suivi et mention prévisionnelle ; **suivi public** `/suivi` (n° + code, initiales seulement, 10 requêtes/min).
- **Espace client**, tableau de bord enrichi (demandes du jour/mois/année, par statut, hors délai).

## Écarts assumés par rapport au cahier des charges
- Statuts regroupés : « En cours de validation » n'existe pas séparément (l'étape administrative est « En attente de validation administrative »).
- Les fêtes mobiles malgaches (Lundi de Pâques, Ascension, Pentecôte) et les fermetures exceptionnelles sont à saisir à la main ; vérifiez aussi la liste fixe.
- Dans l'aperçu d'import, les cases « importer quand même » ne valent que pour la page affichée (50 lignes).
- Pas de vérification d'adresse email à l'inscription, pas de notification par email/SMS (internes seulement).
- La structure des listes est identique pour BEPC, CAE.EP et CAP.EP ; à différencier dans `config/imports.php` si les formats officiels diffèrent.
- Tests du lot 2 écrits (`tests/Feature/WorkflowTest.php`) mais **jamais exécutés**, comme le lot 1.

---
# Lot 3 — ajouts
`php artisan migrate` (migration `2026_10_20_000003`). Guides détaillés dans `docs/`.

- **Attestations** (perte, provisoire, réussite) : éligibilité vérifiée côté serveur, numéro unique, QR, remplacement automatique de l'ancienne, révocation motivée. La réussite est désactivée par défaut (`DIPLOMES_ATTESTATION_REUSSITE`).
- **Registres de notes** : saisie manuelle, aucun calcul automatique, corrections motivées et journalisées, PDF avec QR.
- **Organismes habilités** : le super admin crée un organisme et obtient une clé (stockée hachée) ; page publique `/verification-organisme` (recherche unitaire ou CSV de 200 lignes) ; statuts distincts « non retrouvé » / « données insuffisantes » / « document invalide » ; limite horaire et journalisation.
- **Statistiques** filtrables (`/statistiques`), **journal d'audit** (`/journal-audit`), **sauvegardes** (`/sauvegardes`, `diplomes:sauvegarde`, `diplomes:restaurer`).
- Page publique `/verifier/{jeton}` étendue aux attestations et registres (jamais les notes).

Non réalisé : API JSON pour organismes (seule la page web est fournie), envoi d'emails/SMS, modèles de diplôme CAE.EP / CAP.EP validés, statistiques « par service » (aucune notion de service dans les données), tests exécutés.
