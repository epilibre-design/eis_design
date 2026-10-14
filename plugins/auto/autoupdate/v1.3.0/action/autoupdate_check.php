<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérifier les mises à jour disponibles (hit API forcé, sans cache).
 */
function action_autoupdate_check_dist(): void {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	include_spip('public/aiguiller');
	$redirect = securiser_redirect_action(_request('redirect'))
		?: generer_url_ecrire('autoupdate');

	if (!autoriser('webmestre')) {
		include_spip('inc/headers');
		redirige_par_entete($redirect);
		return;
	}

	include_spip('genie/mise_a_jour');
	$resultat = autoupdate_forcer_verification_maj();

	$redirect = parametre_url($redirect, 'autoupdate_check', $resultat, '&');

	include_spip('inc/headers');
	redirige_par_entete($redirect);
}
