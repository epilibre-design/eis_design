<?php
#-----------------------------------------------------#
#  Plugin  : Couteau Suisse - Licence : GPL           #
#  Auteur  : Patrice Vanneufville, 2007               #
#  Contact : patrice¡.!vanneufville¡@!laposte¡.!net   #
#  Infos : https://contrib.spip.net/?article2166      #
#-----------------------------------------------------#
if(!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');

function exec_cs_boite_rss_dist() {
	cs_minipres();
	// Constantes distantes
	include_spip('cout_define');
	if(defined('_CS_PAS_DE_DISTANT')) {
		ajax_retour(_T('couteauprive:version_distante_off'));
		return;
	}
	$out = '';
	// Recherche du flux rss toutes les _CS_RSS_UPDATE minutes
	$force = _request('force') == 'oui';
	if(!$force) {
		$lastmodified = @file_exists(_CS_TMP_RSS) ? @filemtime(_CS_TMP_RSS) : 0;
		if(time() - $lastmodified < _CS_RSS_UPDATE) lire_fichier(_CS_TMP_RSS, $out);
	}
	if(strlen($out)) {
		ajax_retour($out);
		return;
	}
	include_spip('inc/filtres');
	include_spip('action/editer_site');
	include_spip('inc/xml'); // parse xml
	include_spip('inc/texte'); // pour couper() notamment SPIP 2.x
	include_spip('inc/texte_mini'); // pour couper() notamment
	include_spip('couteau_suisse_fonctions'); // pour cs_lien()
	include_spip('cout_utils'); // pour cs_recuperer_page()
	$out = ''; $nb = 0;
	if(($contenu = cs_recuperer_page($url_source = _CS_GIT_COMMITS)) && ($contenu = json_decode($contenu))) {
		// API de Git
		$nb = count($contenu);
		for($i = 0; $i < min($nb, _CS_RSS_COUNT); $i++) {
			$message = couper(textebrut($contenu[$i]->commit->message) , 110);
			$time = affdate_court(date('Y-m-d', strtotime($contenu[$i]->commit->author->date)));
			$out .= '<li>&bull; ' . cs_lien($contenu[$i]->html_url, $time) . ' - ' . $message . '</li>';
		}
	}
	elseif(($contenu = cs_recuperer_page($url_source = _CS_RSS_SOURCE)) && preg_match('/<table.*<\/table>/si', $contenu, $match)
			&& ($r = spip_xml_parse($match[0], true, true, 3)) && function_exists('spip_xml_match_nodes')
			&& (spip_xml_match_nodes(',^tr,', $r, $r2)) && count($r2['tr'])) {
		// page html publique en cas de changement d'API ?
		$r3 = &$r2['tr'];
		$nb = count($r3);
		for($i = 0; $i < min($nb, _CS_RSS_COUNT); $i++) {
			list($auteur, $sha, $message, $time) = array_values($r3[$i]);
			$time = affdate_court(date('Y-m-d', strtotime(extraire_attribut($time[0], 'title'))));
			$out .= '<li>&bull; ' . str_replace('href="/', 'href="' . _URL_CS_GIT, $sha[0]) . " <i>($time)</i><br/>"
			     . couper(textebrut($message[0]) , 80) . '</li>';
		}
	}
	else {
		$out = '<span style="color: red;">' . _T('couteauprive:erreur:probleme', array(
			'pb' => cs_lien($url_source, _T('couteauprive:erreur:distant'))
		)) . '</span>';
	}
	$time = affdate_heure(date('Y-m-d H:i:s', time()));
	$out = '<ul>' . $out
		. '</ul><p class="rss-small"><b>'
		. _T('couteauprive:rss_edition') . "</b><br/>$time</p>"
		. '<p style="text-align:right"><a href="'
		. generer_url_ecrire('admin_couteau_suisse', 'var_mode=calcul', true) . '" onclick="'
		. "javascipt:jQuery('div.cs_boite_rss').children().css('opacity', 0.5).parent().load('" . generer_url_ecrire('cs_boite_rss', 'force=oui', true) . '\');return false;">'
		. _T('couteauprive:rss_actualiser') . '</a> | <a href="'
		. $url_source . '">' . _T('couteauprive:rss_source') . '</a></p>';
	if($nb) ecrire_fichier(_CS_TMP_RSS, $out);

	ajax_retour($out);
}

?>
