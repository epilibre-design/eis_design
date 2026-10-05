# Changelog

## 1.4.1 - 2026-09-24
feat: La constante `_MAJ_NOTIF_EMAILS` remplace la liste des webmestres pour les notifications  si elle est définie (iso plugin Autoupdate)

## 1.4.0 - 2026-09-20
fix: refuser les préfixes forgés dans l'action groupée
fix(a11y): nommer les cases et listes de sélection pour les lecteurs d'écran
feat: politique de mise à jour par défaut pour les plugins sans réglage

## 1.3.0 - 2026-08-28
feat:  Utilisation du plugin Saisies pour la partie Notifications de la configuration du plugin
feat: préserver les notifications lors de la migration de configuration
feat: affiner le contenu des notifications par courriel
fix: Récupérer les erreurs de l'Actionneur

## 1.2.2 - 2026-01-01

### Fix
fix: Correction configuration action globale

## 1.2.1 - 2025-12-11

### Feat
fix: Un plugin doit être a minima sélectioné pour afficher le bouton 'Appliquer'
fix: Vérification que la tâche majplugins_cron n'est pas en timeout ou erreur fatale au risque de bloquer toute la queue cron

## 1.2.0 - 2025-11-26

### Feat
feat: Ajout de la sélection groupée dans la configuration pour définir un mode de mise à jour

## 1.1.1 - 2025-11-24

### Fix   - Refactor
c8d1795 fix: Correction envoi des emails de notificaton + modification libellé dans fichier lang
f4a07e9 refactor: syntaxe  dépréciée remplacée par {}
c962aef fix: Correction libellé 'majplugins_slogan' dans fichiers langue

## 1.1.0 - 2025-10-14

### Fix - Refactor 
c1e3ebd refactor: Nettoyage de code inutile et utilisation de la fonction native inc_envoyer_mail_dist pour permettre la mise à jour d'un plugin de courriel (Facteur...) sans crash
2337c56 fix: Bloquer les autres tâches cron si majplugins_cron est en cours d'exécution
fc92d49 fix: Actualiser la globale meta et actualiser les plugins actifs en cas de crash_plugin dans le pipeline affichage_final

## 1.0.9 - 2025-10-01

### Fix 
fix: Compléments pour la suppression des messages résiduels de la meta 'message_crash_plugins'

## 1.0.8 - 2025-09-22

### Fix 
fix: Suppression des messages résiduels de la meta 'message_crash_plugins'

## 1.0.7 - 2025-09-18

### Fix 
fix: Invadidation de l'opcache php pour rafraîchir les caches pipelines + message webmaster si crash plugins"

## 1.0.6 - 2025-09-11

### Fix 
fix: Suppression des caches avant actualisation des plugins

## 1.0.5 - 2025-09-10

### Fix 
ecb508a fix: Correction des messages crash_plugins et des télescopages entre plugins
    - Lancement du traitement Cron dans une sous-tâche avec priorité faible pour éviter les collisiions avec d'autres tâches Cron
    - Actualisation des plugins locaux en fin de traitemeent
    - Forçage de la mise à jour du cache méta $GLOBALS[meta] ainsi que du cache meta_cache.php
    - Les logs sont écrites après la fin de la mise à jour pour bénéficier d'un cache pipeline rafraîchi
    - Passage par le pipeline notifications_destinataires

## 1.0.4 - 2025-08-28

### Fix - Refactor 
7caff5d fix: Correction message 'Erreur dans les plugins' dans la page exec=admin_plugin après passage du cron + nettoyage du code inutile
14abb16 fix: Recompilation de tous les caches des plugins en fin de traitement
d9293ac refactor: Regoupement des inclure + suppression log Actions en doublon

## 1.0.3 - 2025-08-20

### Feat - Refactor 
ad24078 feat: Ajout de la navigation de la configuration + déplacement derniers traitements en extra
858546d refactor: Modification libellé fichier lang (fr en es) pour plus de clarté (UX)
0eb7378 feat: Traductions hasardeuses en English et Español
017c34f refactor: Suppression de la class='choix' pour homogénéïté de presentation du formulaire de configuration

## 1.0.2 - 2025-08-15

### Fix - Feat 
5707a51 feat: Ajout lien vers Majplugins dans la page de Gestion des plugins (actifs)
f85494d fix: Passage de la date de traitement en format 24h (H:i:s)
77f4483 fix: Déplacement de l'autorisation plugins_ajouter dans le pipeline autoriser
9e016fd feat: Amélioration écran de configuration affichage gauche + notification

## 1.0.1 - 2025-08-12

### Fix - Feat 
7bb3844 feat: Ajout des dernières actions dans la page de configuration
3b1a1f9 fix: Correction affichage durée du traitement + log intempestives
ff38d95 feat: Ajout des plugins en attente de mise à jour dans le courriel (si demandé)
9e804b7 Fix: Correction de l'autorisation de configuration du plugin
f9d5592 Fix: Correction de l'autorisation de la page de configuration du plugin
f594aa9 Feat: Ajout de la Gestion des plugins dans la navigation

## 1.0.0 - 2025-08-04

### Feat
- Initialisation du plugin Rebond
