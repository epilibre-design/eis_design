<?php

/**
 * Options du plugin Intertitres hierarchiques au chargement.
 *
 * @plugin     Intertitres hierarchiques
 *
 * @copyright  2016
 * @author     Mist. GraphX
 *
 * @licence    GNU/GPL
 *
 * @package    SPIP\Porte_plume_intertitres\Options
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS['spip_wheels']['raccourcis'][] = 'porte_plume_intertitres';

if (!isset($GLOBALS['debut_intertitre'])) {
	$GLOBALS['debut_intertitre'] = '<h2 class="spip">';
	$GLOBALS['fin_intertitre'] = '</h2>';
}
