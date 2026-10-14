<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	//C
	'configuration_majplugins' => 'Mise à jour automatique des plugins',

	//D
	'date_ans' => 'ans',
	'date_un_an' => 'an',
	'date_mois' => 'mois',
	'date_un_mois' => 'mois',
	'date_semaines' => 'semaines',
	'date_une_semaine' => 'semaine',
	'date_jours' => 'jours',
	'date_un_jour' => 'jour',
	'date_heures' => 'heures',
	'date_une_heure' => 'heure',
	'date_minutes' => 'minutes',
	'date_une_minute' => 'minute',
	'date_secondes' => 'secondes',
	'date_une_seconde' => 'seconde',
	'date_millisecondes' => 'millisecondes',
	'date_une_milliseconde' => 'milliseconde',

	//E
	'explication_notification' => 'Si vous souhaitez être prévenu des mises à jour automatiques, activez les notifications',
	'explication_attente_maj' => 'Si vous souhaitez également être prévenu  des plugins en attente de mise à jour, cochez la case',
	'explication_mails' => 'Saisir les adresses emails des destinataires en les séparant par des virgules ",". Ces adresses s\'ajoutent à celle du webmestre du site',
	'explication_notification_maj' => 'Si vous souhaitez être prévenu des mises à jour et des erreurs, cochez la case',
	'explication_notification_erreurs' => 'Si vous souhaitez être prévenu uniquement des erreurs, cochez la case',
	'explication_niveau_defaut' => 'S\'applique aux plugins réglés sur « Par défaut », ainsi qu\'à ceux installés par la suite. Un plugin réglé sur un autre choix, « Aucune » compris, le conserve.',

	//I
    'info_drapeau_cron' => 'Mise à jour de plugins',
    'info_drapeau_en_cours' => 'Une mise à jour automatique est en cours. ',
    'info_drapeau_termine' => 'Une mise à jour automatique vient de se terminer. ',
    'info_drapeau_message' => 'Si un message <i>Erreur dans les plugins</i> apparait, veuillez passer par la page <a href="?exec=admin_plugin">Gestion des plugins</a> pour corriger.',

	//L
    'legende_plugins_actifs' => 'Plugins actifs',
	'legende_notification' => 'Notifications',
	'legende_attente_maj' => 'Plugins en attente',
	'legende_mails' => 'Adresses supplémentaires',
	'label_notification' => 'Notification',
	'label_aucune_notification' => 'Aucune notification',
	'legende_notification_maj' => 'Mises à jour et erreurs',
	'legende_notification_erreurs' => 'Erreurs',
	'legende_niveau_defaut' => 'Politique par défaut',
	'label_niveau_defaut' => 'Mise à jour automatique par défaut',
	'label_niveau_plugin' => 'Niveau de mise à jour de @plugin@',
	'label_action_globale' => 'Niveau des plugins cochés',
	
	//M
    'message_mise_a_jour_sujet' => 'Mise à jour des plugins',
	'message_config_sans_maj' => 'La configuration MajPlugins ne permet pas la mise à jour du plugin « @plugin@ »  (de la version : @version@ à @version_maj@)',
	'message_duree_traitement' => 'Durée du traitement: ',

	//P
    'majplugins_titre' => 'Mise à jour automatique',

	//T
    'titre_page_configurer_majplugins' => 'Configuration de majplugins',
    'titre_derniere_maj' => 'Dernière mise à jour automatique',
    'titre_dernieres_maj' => '@nb@ dernières mises à jour automatiques',
    'titre_derniere_erreur' => 'Dernière erreur',
    'titre_dernieres_erreurs' => '@nb@ dernières erreurs',
	'texte_notification' => 'Activer les notifications des mises à jour automatiques',
	'texte_notification_maj' => 'Notifier les mises à jour et erreurs',
	'texte_attente_maj' => 'Notifier les plugins en attente',
	'texte_notification_erreurs' => 'Notifier uniquement les erreurs',
	'texte_icone_majplugins' => 'Mise à jour automatique',
	'texte_option_maj_aucune' => 'Aucune',
	'texte_option_maj_defaut' => 'Par défaut (@niveau@)',
	'texte_filtre_maj_defaut' => 'Par défaut',
	'texte_option_maj_corrective' => 'Mise à jour corrective (x.y.Z)  automatique',
	'texte_option_maj_fonctionnelle' => 'Mise à jour fonctionelle (x.Y.z) automatique',
	'texte_option_maj_majeure' => 'Mise à jour majeure (X.y.z) automatique',
	
);
