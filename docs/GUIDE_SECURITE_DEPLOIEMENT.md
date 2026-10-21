# Sécurité et déploiement en production

1. `.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, `SESSION_LIFETIME=30`. Ne jamais versionner `.env`.
2. HTTPS obligatoire. Racine web = dossier `public/` uniquement.
3. `composer install --no-dev --optimize-autoloader` ; `php artisan config:cache route:cache view:cache`.
4. **Compte MySQL applicatif** dédié : droits SELECT/INSERT/UPDATE/DELETE/TRIGGER sur la base ; pas de DROP. Renforcer le journal d'audit en retirant UPDATE et DELETE sur `journaux_audit` à ce compte (les triggers le protègent déjà).
5. Changer immédiatement le mot de passe temporaire du super administrateur ; ne pas laisser « motdepasse » en circulation.
6. Dossiers `storage/` et `bootstrap/cache` accessibles en écriture au serveur web seulement ; les pièces et sauvegardes sont dans `storage/app/private` (non publics).
7. Héberger Bootstrap et Chart.js localement (actuellement via CDN) si le serveur est isolé d'Internet.
8. Planificateur et sauvegardes : voir le guide de sauvegarde. Surveiller `storage/logs`.
9. Ne jamais exécuter `diplomes:demo` en production (refusé automatiquement).
10. Faire auditer le code et les droits par un tiers avant ouverture au public : ce projet n'a fait l'objet d'aucun test de pénétration.
11. Données personnelles : durée de conservation, accès et information des personnes à définir avec la Direction.
