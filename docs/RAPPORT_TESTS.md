# Rapport de tests

**Statut : aucun test n'a été exécuté par l'auteur du code.** L'environnement de rédaction ne disposait ni de PHP ni de Composer (ni réseau). Ce rapport liste ce qui est écrit et ce qui reste à exécuter sur votre machine : `php artisan test` (base `diplomes_test` MySQL, les triggers n'existent pas sous SQLite).

| Fichier | Vérifie | Résultat chez vous |
|---|---|---|
| DiplomeSecurityTest | liste verrouillée non modifiable par le saisisseur ; secrétaire sans droit de modification ; impression unique, réimpression autorisée une seule fois par le super admin ; brouillon invisible en public | à renseigner |
| WorkflowTest | cloisonnement des demandes clients ; transitions incohérentes/droits/motif ; délai hors week-ends et fériés ; circuit complet jusqu'à l'impression ; import (erreurs, doublons, ressemblances) | à renseigner |
| Lot3Test | attestations (réussite refusée, provisoire, remplacement, perte) ; vérification organisme (statuts, clé invalide, limite horaire) ; accès réservés ; audit non modifiable | à renseigner |

Non couvert par des tests : génération visuelle des PDF (à contrôler à l'œil sur le modèle officiel), sauvegarde/restauration, import Excel binaire (seul le CSV est testé), concurrence réelle de deux impressions simultanées, performance sur de gros volumes.
