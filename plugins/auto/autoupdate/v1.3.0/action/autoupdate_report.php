<?php

use Spip\Plugin\Autoupdate\Patch\Status;
use Spip\Plugin\Autoupdate\PhpInfo;
use Spip\Plugin\Autoupdate\Report\Sanitizer;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_autoupdate_report_dist(?string $hash = null) {

	if ($hash === null) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$hash = $securiser_action();
	}

	$resultat = 'impossible';
	include_spip('inc/autoupdate');
	if (is_dir($dir = autoupdate_patch_ready_dir($hash)) || is_dir($dir = autoupdate_patch_trash_dir($hash))) {
		// anti double-rapport
		if (!is_file($f = $dir . 'reported.json') || filemtime($f) < time() - 3600) {
			$report = autoupdate_generer_rapport($dir);
			if ($report) {
				if (autoupdate_envoyer_rapport($report)) {
					file_put_contents($f, json_encode($report));
					$resultat = 'ok';
				} else {
					$resultat = 'erreur';
				}
			}
		} else {
			$resultat = 'deja';
		}
	}

	$redirect = _request('redirect');
	if (is_string($redirect) && $redirect !== '') {
		set_request('redirect', parametre_url($redirect, 'autoupdate_report', $resultat, '&'));
	}
}

/**
 * Sanitizer du rapport, branché sur les répertoires SPIP.
 */
function autoupdate_sanitizer_rapport(): Sanitizer {
	static $sanitizer = null;
	if ($sanitizer instanceof Sanitizer) {
		return $sanitizer;
	}
	$root = defined('_DIR_RACINE') ? [_DIR_RACINE] : [];
	$extra = [];
	if (defined('_DIR_TMP')) {
		$extra[] = _DIR_TMP;
	}
	if (defined('_DIR_LOG')) {
		$extra[] = _DIR_LOG;
	}
	$sanitizer = Sanitizer::fromDirectories($root, $extra);

	return $sanitizer;
}

function autoupdate_generer_rapport($dir) {

	$hash = basename($dir);
	$sanitizer = autoupdate_sanitizer_rapport();
	$files = glob($dir . '/*') ?: [];
	$collect = [
		'adresse_site' => $GLOBALS['meta']['adresse_site'],
		'spip' => $GLOBALS['spip_version_branche'],
		'php' => PHP_VERSION,
		'hash' => $hash,
		'files' => array_map([$sanitizer, 'relativizePath'], $files),
	];

	$manifest = file_get_contents($dir . 'manifest-translated.json');
	$collect['manifest-translated'] = json_decode($manifest, true);

	$etapes = [];
	$txts = glob($dir . '*.txt') ?: [];
	foreach ($txts as $f) {
		$etapes[basename($f, '.txt')] = date('Y-m-d H:i:s', filemtime($f));
	}
	$collect['etapes'] = $etapes;

	$collect['progress'] = [];
	if (is_file($f = _DIR_TMP . "autoupdate/progress-{$hash}.txt")) {
		$collect['progress'] = $sanitizer->sanitizeLines((string) file_get_contents($f));
	}

	foreach (['errors', 'processing'] as $txt_file) {
		$collect[$txt_file] = [];
		if (is_file($dir . $txt_file . '.txt')) {
			$collect[$txt_file] = $sanitizer->sanitizeLines(
				(string) file_get_contents($dir . $txt_file . '.txt')
			);
		}
	}

	$collect['redirect_finish'] = '';
	if (is_file($dir . 'redirect_finish.txt')) {
		$collect['redirect_finish'] = $sanitizer->sanitizeText(
			trim((string) file_get_contents($dir . 'redirect_finish.txt'))
		);
	}

	$status = autoupdate_patch_status(autoupdate_patch_ready_dir($hash), true);
	$collect['status'] = $status;

	$collect['log'] = [];
	if (is_file(_DIR_LOG . 'autoupdate.log')) {
		$collect['log'] = $sanitizer->extractLogLines(
			(string) file_get_contents(_DIR_LOG . 'autoupdate.log')
		);
	}

	$collect['phpinfo'] = (new PhpInfo())->essential();

	$status_trad = _T(Status::label($status));
	include_spip('inc/charsets');
	$status_texte = translitteration($status_trad);
	$status_texte = preg_replace(',\W,', '-', $status_texte);

	$info_plugin = chercher_filtre('info_plugin');

	$report = [
		'plugin' => 'autoupdate',
		'version' => $info_plugin('autoupdate', 'version'),
		'status' => empty($collect['errors']) ? $status_texte : 'error',
		'collect' => $collect,
	];
	return $report;
}

/**
 * Envoie le rapport de debug à plugins.spip.net.
 *
 * @param array<string, mixed> $report
 */
function autoupdate_envoyer_rapport($report): bool {
	$url_report = 'https://plugins.spip.net/report.api';
	/** @var int secondes max pour toute la requête */
	$timeout = 30;
	/** @var int secondes max pour établir la connexion */
	$connect_timeout = 10;

	$json = json_encode($report);
	if ($json === false) {
		spip_log('autoupdate_envoyer_rapport: json_encode a échoué', 'autoupdate' . _LOG_ERREUR);
		return false;
	}

	autoupdate_log_resume_rapport($report, strlen($json));

	$ch = curl_init($url_report);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Content-Length: ' . strlen($json),
	]);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	$response = curl_exec($ch);
	$errno = curl_errno($ch);
	$error = curl_error($ch);
	$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if (PHP_VERSION_ID < 80000) {
		curl_close($ch);
	}
	if ($errno || $response === false) {
		spip_log(
			"autoupdate_envoyer_rapport: erreur CURL $errno ($error) http=$http_code",
			'autoupdate' . _LOG_ERREUR
		);
		return false;
	}
	if ($http_code < 200 || $http_code >= 300) {
		spip_log(
			"autoupdate_envoyer_rapport: échec HTTP $http_code sur $url_report",
			'autoupdate' . _LOG_ERREUR
		);
		return false;
	}
	spip_log("autoupdate_envoyer_rapport: sur $url_report OK (HTTP $http_code)", 'autoupdate' . _LOG_DEBUG);
	return true;
}

/**
 * Résumé du payload dans autoupdate.log au moment de l’envoi (pas le JSON complet).
 *
 * @param array<string, mixed> $report
 */
function autoupdate_log_resume_rapport(array $report, int $taille): void {
	$collect = isset($report['collect']) && is_array($report['collect']) ? $report['collect'] : [];
	$hash = isset($collect['hash']) && is_string($collect['hash']) ? $collect['hash'] : '';
	$status = isset($report['status']) && is_string($report['status']) ? $report['status'] : '';
	$cles = $collect !== [] ? implode(',', array_keys($collect)) : '';
	spip_log(
		"autoupdate_envoyer_rapport: envoi hash=$hash status=$status taille={$taille}o collect=[$cles]",
		'autoupdate' . _LOG_INFO_IMPORTANTE
	);
}

/**
 * Hash de patch accepté dans un nom de fichier d’aperçu.
 */
function autoupdate_hash_rapport_valide(string $hash): bool {
	return (bool) preg_match(',\A[a-f0-9]{64}\z,', $hash);
}

/**
 * Rapport déjà envoyé pour ce patch, s’il existe.
 */
function autoupdate_fichier_rapport_envoye(string $hash): ?string {
	if (!autoupdate_hash_rapport_valide($hash)) {
		return null;
	}
	include_spip('inc/autoupdate');
	foreach ([autoupdate_patch_ready_dir($hash), autoupdate_patch_trash_dir($hash)] as $dir) {
		$fichier = $dir . 'reported.json';
		if (is_file($fichier)) {
			return $fichier;
		}
	}

	return null;
}

/**
 * JSON d’aperçu, hors répertoire du patch pour ne pas entrer dans le payload.
 */
function autoupdate_fichier_apercu_rapport(string $hash): ?string {
	if (!autoupdate_hash_rapport_valide($hash)) {
		return null;
	}

	return _DIR_TMP . 'autoupdate/report-preview-' . $hash . '.json';
}

/**
 * Enregistre l’aperçu qui serait envoyé, sans appel réseau.
 *
 * @param array<string, mixed> $report
 */
function autoupdate_stocker_apercu_rapport(string $hash, array $report): bool {
	$fichier = autoupdate_fichier_apercu_rapport($hash);
	if ($fichier === null) {
		return false;
	}
	sous_repertoire(_DIR_TMP, 'autoupdate');
	$json = json_encode(
		$report,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
	if ($json === false) {
		spip_log('autoupdate_stocker_apercu_rapport: json_encode a échoué', 'autoupdate' . _LOG_ERREUR);
		return false;
	}

	return file_put_contents($fichier, $json . "\n") !== false;
}

/**
 * Rend un JSON de rapport lisible, sans changer les données.
 */
function autoupdate_json_rapport_lisible(string $json): string {
	$data = json_decode($json, true);
	if (!is_array($data)) {
		return $json;
	}
	$pretty = json_encode(
		$data,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
	if ($pretty === false) {
		return $json;
	}

	return $pretty . "\n";
}
