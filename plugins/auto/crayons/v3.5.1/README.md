# Crayons v3.5

## Documentation

https://contrib.spip.net/Les-crayons

https://contrib.spip.net/Crayons-Controleurs-et-Vues

## Autorisations

Par défaut toute action de crayonnage appelle `autoriser('crayonner', $type, $id)`
L'autorisation par défaut `autoriser_crayonner_dist()` correspondant vérifie :
* que la table est connue de SPIP
* que l'utilisateur à le droit de modifier l'objet #id correspondant
* que les champs que l'on veut modifier sont bien déclarés comme `champs_editables` dans la déclaration de l'objet

Dans la plupart des cas usuels, l'autorisation doit donc être correcte par défaut.

Dans le cas où
* l'utilisateur veut crayonner un champ non déclaré comme editable l'autorisation `autoriser('crayonnerchampnoneditable', $type, $id)` est appelée. Par défaut elle réserve l'action aux administrateurs.
* l'utilisateur veut crayonner une table sur un connect différent de `''` l'autorisation `autoriser('crayonnertabledistante', $type, $id)` est appelée. Par défaut elle réserve l'action aux administrateurs.
* l'utilisateur veut crauonner une table non connue comme table_principale ou table_auxiliaire, l'autorisation `autoriser('crayonnertableexterne', $type, $id)` est appelée. Par défaut elle réserve l'action aux administrateurs.

Dans le cas où une autorisation est manquante pour votre cas d'usage, et cela produit un refus de crayonnage il est recommandé
* de vérifier la déclaration de `champs_editables` pour la table concernée, et de la compléter si besoin
* de vérifier les droits renvoyés par le `autoriser('modifier', $type, $id)` de la table concernée
* de vérifier la déclaration de la table, et de la corriger si besoin
* de définir l'une des 3 autorisations `crayonnerchampnoneditable`, `crayonnertabledistante` ou `crayonnertableexterne` ci-dessus pour votre objet

Pour des raisons de sécurité, il n'est pas recommandé de définir dirrectement une autorisation crayonner pour votre objet,
sauf à y réintégrer toutes les vérifications de droits faites dans la fonction par défaut du plugin.

## Notes

### Definir de maniere personalisee quel input mode (ligne ou texte) utiliser champ par champ:
Exemple de fonction personnalisée (à mettre dans le *mes_options.php* par exemple)

cf https://git.spip.net/spip-contrib-extensions/crayons/commit/a91edba87999a1c2670df9f5c107eba1f1ac1729
```
/**
 * imposer textarea sur les crayons pour des champs extra de la table spip_rubriques
 * cette fonction est appelée auto-magiquement par action/crayons_html.php
 *
 * @param $type : l'objet SPIP (article, rubrique...)
 * @param $champ : le nom du champ
 * @param $sqltype la description SQL du champ (?)
 * @return : ligne | texte
 *
 **/
function crayons_determine_input_mode_type_rubrique($type, $champ, $sqltype) {
	// array de tous les champs devant êtres en textarea
	$Ttextarea = array('titre_machin', 'texte_truc', 'champ_bidule');

	return in_array($champ, $Ttextarea) ? 'texte' : 'ligne';
}
```
