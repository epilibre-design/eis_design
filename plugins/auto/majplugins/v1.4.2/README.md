# MajPlugins pour SPIP

Automatisation de la mise à jour des plugins actifs et non vérouillés. Le traitement est lancé par le Cron SPIP.
Il reprend les fonctionalités de la page de "Gestion des plugins" (plugin SVP de la dist), mais seule l'action de mise à jour (up) est possible: pas de désactivation (off) ou de désinstallation (stop).

## Configuration du plugin:

- Sélection des plugins à mettre à jour dans les plugins non vérouillés actifs du site
- Politique par défaut: niveau appliqué aux plugins sans réglage propre, y compris ceux installés par la suite (corrective tant qu'elle n'est pas configurée)
- Sélection du niveau de mise à jour souhaité par plugin:
	- par défaut (suit la politique par défaut)
	- aucune
	- corrective
	- fonctionnelle
	- majeure
- Un réglage propre à un plugin, « aucune » compris, n'est jamais remplacé par la politique par défaut
- MajPlugins ne se met jamais à jour lui-même, et les plugins-dist ne sont pas concernés

## Cinématique:

- Lecture des mises à jour disponibles pour les plugins actifs (plugins/paquets), hors plugins-dist et hors MajPlugins
- Pour chacun, niveau de mise à jour autorisé : son réglage propre, à défaut la politique par défaut
- Pour chaque mise à jour de plugin permise par ce niveau:
	- Détermination des actions à réaliser (Décideur)
	- Traitement des mises à jours (Actionneur)
- Actualisation des tables des plugins et des paquets du site (Dépoteur)
- Ecriture de la log et envoi d'un courriel si demandé
	
## Notes

- Le plugin MajPlugins nécessite le plugin de la dist SVP dont il reprend les princupales fonctions (Classes) et feuilles de style
- L'autorisation autoriser_plugins_ajouter_dist est surchargée pour l'étendre au traitemenr Cron
- Le traitement Cron est lancé par défaut toutes les 24 heures. Il est posible de forcer cette valeur en alimentant la constante ``_MAJPLUGINS_CRON_FREQUENCE`` (en secondes)
- Quelques libellés du fichier de langue svp_fr sont également surchargés pour une meilleure lecture des logs et courriels
	
## Documentation

https://contrib.spip.net/MajPlugins

