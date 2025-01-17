# Intertitres pour le porte plume

Nativement spip ne propose qu'un seul niveau de titre/intertitre.
Le niveau de départ `h3.spip` est configurable depuis `_options.php`,
en utilisant les [variables de personalisations](http://www.spip.net/fr_article1825.html#debut_intertitre) :

Ce plugin ajoute au porte plume de [spip](http://www.spip.net/)
la gestion de niveaux de titres supplémentaires, en prenant en compte le niveau de départ configuré dans spip
ou `mes_options.php`.

```php

$GLOBALS['debut_intertitre'] = "\n<h2 class=\"spip\">\n";
$GLOBALS['fin_intertitre'] = "</h2>\n";

```

Le plugin reprend les syntaxes proposées par typo-enluminée, et intertitres_tdm,
soit `{{{***` pour les titres simples ou `{{{###` pour les titres de type référence (ex:1.1, 1.1.2).

Les icones affichées dans le porte plume (barre typographique),
reflète le niveau sémantique au sens html pour sensibiliser le rédacteur à son plan de page.
Le niveau se base sur la globale spip debut_intertitre.


**Différences avec les plugins typo enluminé ou intertitres_hierarchise_et_tdm :**

*	ce plugin utilise [textwheel](http://contrib.spip.net/Presentation-de-TextWheel),
ceci permettant entre autre :

	- de pouvoir surcharger les wheels déclarées pour un besoin spécifique,
	- tester et maintenir plus facilement les expressions/ étudiées/raccourcis traités.
	- utiliser les fonctionnalitées de spip3

*	il ne gère que les titres : ce plugin n'apporte **que** cette fonctionnalité.
*	une syntaxe additionnelle permet d'ajouter des classes css supplémentaires et une id spécifique au titre.

**Plugins complémentaires testés**

*   [Sommaire automatiques](http://contrib.spip.net/Sommaire-automatique).

## Raccourcis

| Raccourci                                                                      | Description |
|--------------------------------------------------------------------------------|---|
| `{{{* … }}}`                                                                   | Titre standard  équivalent au raccourcis spip intertitre `{{{…}}}` |
| `{{{** Titre standard }}}`                                                     ||
| `{{{** Titre standard }}}`                                                     ||
| `{{{*** Titre standard }}}`                                                    ||
| `{{{**** Titre standard }}}`                                                   ||
| `{{{***** Titre standard }}}`                                                  ||
| `{{{# Titre de type référence }}}`                                             ||
| `{{{## Titre de type référence }}}`                                            ||
| `{{{### Titre de type référence }}}`                                           ||
| `{{{#### Titre de type référence }}}`                                          ||
| **Attributs**                                                                  | Les attributs additionnels sont inclus si nécessaire dans une chaine incluse entre parenthèses et accolée sans espace au raccourci du titre. Il est possible d’ajouter plusieurs attributs en les séparant par un espace : `{raz .classe #id} `par exemple |
| `{{{ Titre }}}{raz}`                                                           | ’extension raz permet de remettre à zéro la numérotation des titres. Cette fonctionnalité est à utiliser sur le premier titre d’un champ d’objet si l’on veut insérer des titres dans plusieurs champs d’un même objet |
| `{{{ Titre }}}{.test-class1 .test--extender}`                                  | Ajouter des attributs css suplémentaires aux titres |
| `{{{ Titre }}}{#id_du_titre}`                                                  | Inssérer un identifiant unique |
| `{{{ Titre }}}{attribut="valeur attribut" data-appear="left" itemprop="name"}` | Inssérer des propriétées/attributs libres |



## A savoir

*Si  les icones du porte plume n'apparaissent pas après l'activation du plugin,
supprimez les dossiers /local/cache-css et js.*

*si vous avez installé une version précédente, il peut être nécessaire
de supprimer le dossier /tmp/cache/wheels, pour que les traitements typo soient pris en compte.*

## Participer

Tout retour est apprécié : suggestions, tests, bugs, idées d'amélioration, pull request.


## TODO

- [X] 	ajouter la possibilitée de ne pas afficher les titres référence dans la barre d'outil sur globale ou config ?
- [ ] 	ajout d'ancre avec url pour permettre d'inclure des liens facilement vers une partie du texte :
		https://assortment.io/posts/simple-automated-jumplinks-with-jquery
		ou anchor.js

- [ ] 	Afficher dans la page config le niveau de la globale
		```php
		    $GLOBALS['debut_intertitre'] = '<h2 class="spip">';
			$GLOBALS['fin_intertitre'] = '</h2>';
		```
- [X] 	Afficher l'icone correspondant au niveau de titre réelle en fonction de la globale debut_intertitre

