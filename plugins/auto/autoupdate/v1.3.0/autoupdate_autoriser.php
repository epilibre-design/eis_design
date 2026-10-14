<?php

/**
 * Autorisations du plugin Autoupdate
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction du pipeline autoriser. N'a rien à faire.
 *
 * @pipeline autoriser
 */
function autoupdate_autoriser() {
}

/**
 * Autorisation de voir le menu Autoupdate (Maintenance)
 *
 * Réservé aux webmestres.
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 */
function autoriser_autoupdate_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('webmestre');
}
