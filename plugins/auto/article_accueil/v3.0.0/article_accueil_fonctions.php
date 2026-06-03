<?php

/**
 * Critère `(ARTICLES){article_accueil}` qui sélectionne des articles d’accueil…
 *
 * - `{!article_accueil}` fait l’inverse.
 *
 * @critere
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_ARTICLES_article_accueil_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$id_table = $boucle->id_table; // articles
	$primary = $boucle->primary; // id_article

	$boucle->from['rubriques'] = 'spip_rubriques';
	$boucle->join['rubriques'] = array("'$id_table'","'id_rubrique'","'id_rubrique'");

	$where = "'rubriques.id_article_accueil = $id_table.$primary'";
	if ($crit->not) {
		$where = array("'NOT'", $where);
	}
	$boucle->where[]= $where;
}
