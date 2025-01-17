<?php


if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Prendre la date courante et ajuste à un j+1, 00:00:00
 * @param str $date
 * @return str date
**/
function full_calendar_jplusun($date) {
	$date = new DateTime($date);
	$date->setTime(0,0,0);
	$date->add(new DateInterval('P1D'));
	return $date->format('Y-m-d H:i:s');
}

/**
 * Prend un tableau et supprime et supprime une clé
 * @param array $array
 * @param string $key
 * @return array
**/
function full_calendar_array_remove_key(array $array, string $key): array {
	unset($array[$key]);
	return $array;
}
