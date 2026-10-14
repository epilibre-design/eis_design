# Changelog

## 1.3.0 - 2026-10-07

### Added

- #24 Indiquer le statut d’envoi du rapport
- #24 Permettre de voir un rapport de debug

### Fixed

- #24 Logs du rapport en tableau, plus lisible
- #24 Envoyer un rapport de debug si configuré automatiquement aussi sur les erreurs
- #24 Ne pas planter avec phpinfo absent
- #24 Limiter et anonymiser encore plus le rapport envoyé


## 1.2.1 - 2026-10-06

### Fixed

- Un patch ou un rollback qui ne modifie aucun fichier de plugin doit fonctionner

## 1.2.0 - 2026-10-05

### Security

- Demander un password pour rollback

### Fixed

- Meilleure présentation des rollbacks
- #22 Permettre des rollback depuis une version passée également
- #22 Stocker le last-modified de l’api pour vérifier sa fraîcheur
- Utiliser les fonctions message_alerte dédiées
- Réduire les logs de la page de statut (sauf pour la dernière maj)
- #20 Indiquer la version cible d'un patch de mise à jour pour la version courante
- #20 Quelques wording pour les statuts de mise à jour
- #20 Wording configuration du rapport de debug
- Si l’upgrade de base a déjà été fait, on ne fait pas l’upgrade de base même si les versions ne correspondent pas.
- #19 Ne pas couper PHP sur les requêtes curl courtes + log des fatals dans tmp/log/autoupdate.log

## 1.1.0 - 2026-09-25

### Added

- #1 Vérifier le .htaccess également
- #7 #6 Page (minimale) Maintenance > Auto Update
- #6 Bouton pour forcer la vérification des mises à jour

### Fixed

- #10 Ajouter un refresh au bout de 10s si on a pas fini
- Supprimer spip-update.php racine si trop vieux.
- #8 Envoyer un mail en cas d'échec de mise à jour également
- Légère amélioration de présentation de la page de status
- #3 Wording et explication de la configuration
- #4 Les fichiers absents sont traités comme ajouts (si option ignore_changed_files activée)
- #13 deprecated curl_close

