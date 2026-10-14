<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_once _DIR_RACINE . 'ecrire/genie/mise_a_jour.php';

/**
 * Verifier si une mise a jour est disponible
 *
 * @param int $t
 * @return int
 */
function genie_mise_a_jour($t) {
	autoupdate_nettoyer_spip_update_racine();

	genie_mise_a_jour_dist($t);

	// Déclencher automatiquement une mise à jour mineure si configuré comme tel
	include_spip('inc/config');
	if (lire_config('autoupdate/mode', 'manuel') !== 'manuel') {
		$maj = info_maj_exists($GLOBALS['spip_version_branche']);
		if ($maj['mineure']) {
			include_spip('inc/autoupdate');
			if (autoupdate_urls_patch($GLOBALS['spip_version_branche'])) {
				$autoupdate = charger_fonction('autoupdate', 'action');
				$autoupdate($GLOBALS['spip_version_branche']);
			}
		}
	}

	return 1;
}

/**
 * Supprime un spip-update.php oublié à la racine s'il est trop vieux.
 *
 * Même TTL que dans spip-update.php (600 s).
 */
function autoupdate_nettoyer_spip_update_racine(): void {
	$spip_update = _DIR_RACINE . 'spip-update.php';
	if (!file_exists($spip_update)) {
		return;
	}
	$mtime = @filemtime($spip_update);
	if ($mtime === false || $mtime < time() - 600) {
		@unlink($spip_update);
		spip_log('autoupdate_nettoyer_spip_update_racine: suppression de spip-update.php périmé à la racine', 'autoupdate' . _LOG_INFO_IMPORTANTE);
	}
}

/**
 * Chemin du cache local pour l'API spip_loader (catalogue ou projection `/3/{from}`).
 *
 * @param int|string $version_api
 */
function autoupdate_cache_nom_maj_v($version_api, ?string $from = null): string {
	$nom = _DIR_CACHE . basename(_VERSIONS_LISTE, '.json') . "-$version_api";
	if ($from !== null && $from !== '') {
		$safe = preg_replace('/[^0-9A-Za-z.-]/', '_', $from);
		$nom .= '-' . $safe;
	}

	return $nom . '.json';
}

/**
 * Retourne (et sauvegarde) la liste des mises à jour de SPIP
 *
 * On teste la nouveauté par If-Modified-Since. Le mtime du fichier local
 * est aligné sur le Last-Modified d'origine (`touch`) : s'il restait à
 * l'heure d'écriture locale, `recuperer_entetes_complets()` transformerait
 * les 200 suivants en 304 et jetterait le corps — cache v3 figé.
 *
 * Le JSON n'est réécrit que si le corps change. En cas de faux 304
 * (mtime local > Last-Modified reçu), un hit sans IMS récupère le document.
 * La vérification manuelle (`$force`) ignore cache curl et IMS.
 *
 * @see info_maj()
 * @internal
 * @param int|string $version_api Version de l'API (ex. 3)
 * @param bool $force Si true, hit HTTP immédiat sans cache curl ni IMS
 * @param string|null $from Version installée pour l'API `/3/{from}` (projection partiels / rollbacks)
 * @return array|null Contenu du fichier de cache de l'info de maj de SPIP.
 */
function info_maj_cache_v($version_api, bool $force = false, ?string $from = null): ?array {
	$contenu = null;
	$options = [];
	$nom = autoupdate_cache_nom_maj_v($version_api, $from);
	$url = _VERSIONS_SERVEUR . "/$version_api";
	if ($from !== null && $from !== '') {
		$url .= '/' . rawurlencode($from);
	}

	if (file_exists($nom)) {
		$contenu = file_get_contents($nom);
		if (!$force) {
			$options['if_modified_since'] = filemtime($nom);
		}
	}
	if ($force) {
		// Vérification manuelle : pas de cache curl, pas d'IMS (débloque un cache figé)
		$options['delai_cache'] = 0;
	}

	include_spip('inc/distant');
	$res = recuperer_url_cache($url, $options);

	// Faux 304 : mtime empoisonné > Last-Modified d'origine → refetcher sans IMS
	if (
		!$force
		&& $res
		&& empty($res['page'])
		&& !empty($res['last_modified'])
		&& file_exists($nom)
		&& filemtime($nom) > (int) $res['last_modified']
	) {
		$res = recuperer_url_cache($url, ['delai_cache' => 0]);
	}

	// Corps neuf : n'écrire que s'il diffère
	if ($res && !empty($res['page'])) {
		if ($contenu === null || $res['page'] !== $contenu) {
			$contenu = $res['page'];
			ecrire_fichier($nom, $contenu);
		}
	}

	// Aligner le mtime sur le Last-Modified d'origine (prochains IMS)
	if ($res && !empty($res['last_modified']) && file_exists($nom)) {
		@touch($nom, (int) $res['last_modified']);
	}

	if (!$contenu) {
		return null;
	}

	try {
		$json = json_decode($contenu, true, 512, JSON_THROW_ON_ERROR);
	} catch (JsonException $e) {
		spip_log('Failed to parse Json data : ' . $e->getMessage(), 'verifie_maj');
		return null;
	}

	return $json;
}

/**
 * Force un hit à l'API des versions (v1 + v3) puis met à jour les metas SPIP.
 *
 * N'applique pas de mise à jour, même en mode automatique.
 *
 * @return string Code résultat : a_jour | disponible | disponible_patch | erreur
 */
function autoupdate_forcer_verification_maj(): string {
	include_spip('inc/distant');

	// API v3 (patchs) : forcer le hit HTTP pour la version installée
	$infos_v3 = info_maj_cache_v(3, true, $GLOBALS['spip_version_branche']);

	// API v1 (liste des versions) : invalider le cache curl puis laisser le génie core travailler
	$options = ['delai_cache' => 0];
	$nom = _DIR_CACHE . _VERSIONS_LISTE;
	if (file_exists($nom)) {
		$options['if_modified_since'] = filemtime($nom);
	}
	$res_v1 = recuperer_url_cache(_VERSIONS_SERVEUR, $options);
	if ($res_v1 && !empty($res_v1['page'])) {
		ecrire_fichier($nom, $res_v1['page']);
	}

	if ($infos_v3 === null && (empty($res_v1) || empty($res_v1['page'])) && !file_exists($nom)) {
		return 'erreur';
	}

	// Mettre à jour meta info_maj_spip (sans déclencher d'autoupdate)
	genie_mise_a_jour_dist(0);

	$maj = info_maj_exists($GLOBALS['spip_version_branche']);
	if (!$maj['mineure'] && !$maj['majeure']) {
		return 'a_jour';
	}

	include_spip('inc/autoupdate');
	if ($maj['mineure'] && autoupdate_urls_patch($GLOBALS['spip_version_branche'])) {
		return 'disponible_patch';
	}

	return 'disponible';
}
