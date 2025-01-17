<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/configureragenda?lang_cible=en
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'avertissement_affiche_inscription_definie' => '<b>Caution:</b> the global variable <code>$GLOBALS[\'agenda_affiche_inscription\']</code> is defined. Its value replaces the configuration selected above.',
	'avertissement_effacer_evenements' => 'Warning: this option irreversibly deletes data from the database',

	// E
	'explication_timezone_defaut' => 'The default time zone (@timezone@) will be used to store dates in the database ',

	// L
	'label_affichage_debut' => 'Top of the list',
	'label_affichage_debut_date_jour' => 'Today’s date',
	'label_affichage_debut_date_veille' => 'Yesteday’s date',
	'label_affichage_debut_debut_mois' => 'Beginning of the month',
	'label_affichage_debut_debut_mois_1' => 'Début d’année',
	'label_affichage_debut_debut_mois_prec' => 'Début du mois précédent',
	'label_affichage_debut_debut_semaine' => 'Beginning of the week',
	'label_affichage_debut_debut_semaine_prec' => 'Beginning of the previous week',
	'label_affichage_debut_mois_passe' => 'previous @mois@',
	'label_affichage_duree' => 'List events on',
	'label_articlepardefaut' => 'Identification of default article',
	'label_delai_effacer_evenements_passes' => 'Delay before deleting (days)',
	'label_descriptif' => 'Descriptive',
	'label_effacer_evenements_passes' => 'Delete past events',
	'label_fuseaux_horaires_1' => 'Support time zones on events',
	'label_insert_head_css_1' => 'Automatically insert the default styles of the agenda',
	'label_notifier_insitituer_1' => 'Send notifications of proposed and published events to the admins',
	'label_synchro_statut_1' => 'Events are automaticaly published/unpublished with the article they are linked to',
	'label_titre' => 'Page title',
	'label_url_evenement' => 'Event display',
	'label_url_evenement_article' => 'on the page of the related article',
	'label_url_evenement_evenement' => 'on a dedicated page for each event',
	'legend_nettoyage_agenda' => 'Periodic cleaning of the agenda',
	'legend_presentation_agenda' => 'Agenda display',
	'legend_presentation_agenda_prive' => 'Agenda display in the admin area',
	'legend_presentation_agenda_public' => 'Agenda display on the public site',

	// T
	'texte_contenu_evenements' => 'Depending on the layout of your site, you may decide that certain elements of the events are not used. Use the list below to indicate which elements are available.',
	'titre_affichage_agenda_public' => 'Agenda display on the public site',
	'titre_configuration' => 'Agenda configuration',
	'titre_contenu_evenements' => 'Content of the events'
);
