# Sauvegarde et restauration

**Prérequis** : `mysqldump` et `mysql` accessibles (sinon `DIPLOMES_MYSQLDUMP` / `DIPLOMES_MYSQL` dans `.env`).

- Manuelle : `php artisan diplomes:sauvegarde` ou bouton dans **Sauvegardes**. Fichiers : `storage/app/private/sauvegardes/diplomes-AAAAMMJJ-HHMMSS.sql.gz` (30 derniers conservés).
- Automatique : ajouter à `routes/console.php`
  `\Illuminate\Support\Facades\Schedule::command('diplomes:sauvegarde')->dailyAt('02:00');`
  et planifier la tâche système : `* * * * * cd /chemin/projet && php artisan schedule:run >> /dev/null 2>&1`
- **Copiez les sauvegardes hors du serveur** (autre machine, support chiffré). Une sauvegarde sur le même disque ne protège pas d'une panne du disque.
- Restauration (CLI uniquement, écrase la base) : `php artisan diplomes:restaurer diplomes-20261021-020000.sql.gz` puis saisir le nom de la base pour confirmer.
- **Testez la restauration** sur une base vide au moins une fois avant la mise en production. Les triggers sont inclus dans la sauvegarde.
