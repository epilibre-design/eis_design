<?php

use Spip\Plugin\Autoupdate\Patch\Status;
use Spip\Plugin\Autoupdate\PhpInfo;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_autoupdate_report_dist(?string $hash = null) {

	if ($hash === null) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$hash = $securiser_action();
	}

	include_spip('inc/autoupdate');
	if (is_dir($dir = autoupdate_patch_ready_dir($hash)) || is_dir($dir = autoupdate_patch_trash_dir($hash))) {
		// anti double-rapport
		if (!is_file($f = $dir . 'reported.json') || filemtime($f) < time() - 3600) {
			$report = autoupdate_generer_rapport($dir);
			if ($report) {
				autoupdate_envoyer_rapport($report);
				file_put_contents($f, json_encode($report));
			}
		}
	}
}

function autoupdate_generer_rapport($dir) {

	$hash = basename($dir);
	$collect = [
		'adresse_site' => $GLOBALS['meta']['adresse_site'],
		'spip' => $GLOBALS['spip_version_branche'],
		'php' => PHP_VERSION,
		'hash' => $hash,
		'files' => glob($dir . '/*'),
	];

	$manifest = file_get_contents($dir . 'manifest-translated.json');
	$collect['manifest-translated'] = json_decode($manifest, true);

	$etapes = [];
	$txts = glob($dir . '*.txt');
	foreach ($txts as $f) {
		$etapes[basename($f, '.txt')] = date('Y-m-d H:i:s', filemtime($f));
	}
	$collect['etapes'] = $etapes;

	$collect['progress'] = '';
	if (is_file($f = _DIR_TMP . "autoupdate/progress-{$hash}.txt")) {
		$collect['progress'] = file_get_contents($f);
	}

	foreach (['errors', 'processing', 'redirect_finish'] as $txt_file) {

		$collect[$txt_file] = '';
		if (is_file($dir . $txt_file . '.txt')) {
			$collect[$txt_file] = file_get_contents($dir . $txt_file . '.txt');
		}
	}

	$status = autoupdate_patch_status(autoupdate_patch_ready_dir($hash), true);
	$collect['status'] = $status;

	$collect['log'] = '';
	if ($log = file_get_contents(_DIR_LOG . 'autoupdate.log')) {
		$collect['log'] = $log;
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

function autoupdate_envoyer_rapport($report) {
	$url_report = 'https://plugins.spip.net/report.api';

	$json = json_encode($report);
	$ch = curl_init($url_report);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Content-Length: ' . strlen($json),
	]);
	$response = curl_exec($ch);
	$errno = curl_errno($ch);
	if (PHP_VERSION_ID < 80000) {
		curl_close($ch);
	}
	if ($errno) {
		spip_log("autoupdate_envoyer_rapport: erreur CURL $errno", 'autoupdate' . _LOG_ERREUR);
		return false;
	}
	spip_log("autoupdate_envoyer_rapport: sur $url_report OK", 'autoupdate' . _LOG_DEBUG);
	return true;
}
