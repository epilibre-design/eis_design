<?php

	function remaintingPourcentage() {
		// Définir la zone horaire, si nécessaire
		date_default_timezone_set('Europe/Paris');

		// Créer les instances de DateTime pour les deux dates spécifiées
		$startDate = new DateTime("2023-11-01");
		$startDate->setTime(0, 0);
		$endDate = (new DateTime("2023-11-04"))->setTime(15, 0);

		// Calculer l'intervalle total
		$totalInterval = $endDate->diff($startDate);
		$totalSeconds = $totalInterval->days * 24 * 60 * 60 + $totalInterval->h * 60 * 60 + $totalInterval->i * 60 + $totalInterval->s;

		// Créer une instance de DateTime pour le moment actuel
		$now = new DateTime();

		// S'assurer que "maintenant" est entre les deux dates pour calculer les pourcentages correctement
		if ($now < $startDate || $now > $endDate) {
		echo ("La date et l'heure actuelles ne sont pas dans la plage entre le début et la fin spécifiés.");
		return;
		}

		// Calculer les secondes écoulées depuis le début de la période
		$elapsedSinceStart = $now->getTimestamp() - $startDate->getTimestamp();

		// Calculer le pourcentage de temps écoulé
		$elapsedPercentage = ($elapsedSinceStart / $totalSeconds) * 100;

		// Calculer les secondes restantes jusqu'à la fin de la période
		// $remainingUntilEnd = $endDate->getTimestamp() - $now->getTimestamp();

		// Calculer le pourcentage de temps restant
		// $remainingPercentage = ($remainingUntilEnd / $totalSeconds) * 100;

		// Afficher les résultats
		//echo number_format($remainingPercentage,0);
		return number_format($elapsedPercentage,0);
	}

?>