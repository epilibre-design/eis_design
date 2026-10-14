<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	//C
	'configuration_majplugins' => 'Actualización automática de los plugins',

	//D
	'date_ans' => 'años',
	'date_un_an' => 'año',
	'date_mois' => 'meses',
	'date_un_mois' => 'mes',
	'date_semaines' => 'semanas',
	'date_une_semaine' => 'semana',
	'date_jours' => 'días',
	'date_un_jour' => 'día',
	'date_heures' => 'horas',
	'date_une_heure' => 'hora',
	'date_minutes' => 'minutos',
	'date_une_minute' => 'minuto',
	'date_secondes' => 'segundos',
	'date_une_seconde' => 'segundo',
	'date_millisecondes' => 'milisegundos',
	'date_une_milliseconde' => 'milisegundo',

	//E
	'explication_notification' => 'Si desea recibir avisos sobre las actualizaciones automáticas, active las notificaciones',
	'explication_attente_maj' => 'Si desea también recibir avisos sobre los plugins pendientes de actualización, marque la casilla',
	'explication_attente_alerte' => 'Si desea tambien recibir avisos sobre fallas de tables y plugins , marque la casilla',
	'explication_mails' => 'Introduzca las direcciones de correo electrónico de los destinatarios separadas por comas ",". Estas direcciones se añaden a la del webmaster del sitio',
	'explication_notification_maj' => 'Si desea recibir avisos sobre las actualizaciones y los errores, marque la casilla',
	'explication_notification_erreurs' => 'Si desea recibir avisos únicamente sobre errores, marque la casilla',
	'explication_niveau_defaut' => 'Se aplica a los plugins configurados en «Por defecto» y a los que se instalen después. Un plugin con otra opción, incluida «Sin actualización», la conserva.',

    // I
    'info_drapeau_cron' => 'Actualización de plugins',
    'info_drapeau_en_cours' => 'Hay una actualización automática en curso. '
    'info_drapeau_termine' => 'Acaba de finalizar una actualización automática. ',
	'info_drapeau_message' => 'Si aparece un mensaje de <i>Error en los plugins</i>, vaya a la página <a href="?exec=admin_plugin">Gestión de plugins</a> para solucionar el problema.',

	//L
    'legende_plugins_actifs' => 'Plugins activos',
	'legende_notification' => 'Notificaciones',
	'legende_attente_maj' => 'Plugins pendientes de actualización',
	'legende_legende' => 'fallas',
	'legende_mails' => 'Direcciones adicionales',
	'label_notification' => 'Notificación',
	'label_aucune_notification' => 'Sin notificación',
	'legende_notification_maj' => 'Actualizaciones y errores',
	'legende_notification_erreurs' => 'Errores',
	'legende_niveau_defaut' => 'Política por defecto',
	'label_niveau_defaut' => 'Actualización automática por defecto',
	'label_niveau_plugin' => 'Nivel de actualización de @plugin@',
	'label_action_globale' => 'Nivel de los plugins marcados',
	
	//M
    'message_mise_a_jour_sujet' => 'Actualización de plugins',
	'message_config_sans_maj' => 'La configuración de MajPlugins no permite la actualización del plugin “@plugin@” (de la versión: @version@ a @version_maj@)',
	'message_duree_traitement' => 'Duración del proceso: ',

	//P
    'majplugins_titre' => 'Actualización automática',
	
	//T
    'titre_page_configurer_majplugins' => 'Configuración de MajPlugins',
    'titre_derniere_maj' => 'Última actualización',
    'titre_dernieres_maj' => 'Últimas @nb@ actualizaciones',
    'titre_derniere_erreur' => 'Último error',
    'titre_dernieres_erreurs' => 'Últimos @nb@ errores',
	'texte_notification' => 'Activar las notificaciones de actualizaciones automáticas',
	'texte_notification_maj' => 'Notificar las actualizaciones y los errores',
	'texte_attente_maj' => 'Notificar los plugins pendientes',
	'texte_notification_erreurs' => 'Notificar únicamente los errores',	'texte_icone_majplugins' => 'Actualización automática',
	'texte_option_maj_aucune' => 'Sin actualización ',
	'texte_option_maj_defaut' => 'Por defecto (@niveau@)',
	'texte_filtre_maj_defaut' => 'Por defecto',
	'texte_option_maj_corrective' => 'Actualización correctiva automática (x.y.Z)',
	'texte_option_maj_fonctionnelle' => 'Actualización funcional automática (x.Y.z)',
	'texte_option_maj_majeure' => 'Actualización mayor automática (X.y.z)',
	
);