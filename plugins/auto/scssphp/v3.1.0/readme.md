# SCSSPHP pour SPIP 4.1+

Compile en CSS et mets en cache un fichier SCSS (https://sass-lang.com/)
grace au compilateur ScssPHP https://scssphp.github.io/scssphp/

Le plugin requiert PHP 7.4 ou plus

Permet d'utiliser des feuilles de style scss dans ses squelettes et thèmes,
et permet d'utiliser notamment le plugin BootStrap4 https://git.spip.net/spip-contrib-extensions/bootstrap4


## Exemple

Inclusion du fichier css/scss_demo.scss dans un squelette SPIP

```
[<link rel="stylesheet" href="(#CSS{css/scss_demo.css})" type="text/css" />]
```

La balise `#CSS`
* gère le mélange des feuilles `.css` et `.scss` : on peut surcharger une feuille `.css` d'un squelette par une feuille `.scss`
* compile les feuilles `.scss` à la volée automatiquement
* applique automatiquement les filtres `|direction_css|timestamp`

Exemple complet d'utilisation dans un squelette :
https://git.spip.net/spip-contrib-squelettes/spipr-dist/src/branch/master/inclure/head.html

## Demo

Appel de la démo via `./?page=demo/test_scss`
(logé administrateur)

## Utilisation sur poste de développement avec xDebug

Si l'extension PHP xDebug est activée, la compilation des SCSS est très lente car le compilateur demande beaucoup de calcul.
Il est alors possible d'échapper à cette lenteur en compilant en cli pour utiliser une instance PHP sans xdebug.

Pour cela
* il faut avoir installé spip-cli https://contrib.spip.net/SPIP-Cli
* il faut que la commande `exec()` soit autorisée
* il faut ajouter dans son fichier `mes_options.php` la ligne
```
define('_SCSSPHP_SPIP_CLI_COMPILE', true);
```
Si besoin il est possible de definir le path de spip-cli via une seconde ligne (par défaut c'est `/usr/local/bin/spip`)
```
define('_SCSSPHP_SPIP_CLI_BIN', '/path/to/spip');
```

Cette fonctionnalité permet aussi d'executer la compilation SCssPHP sur une version PHP plus récente que celle utilisée pour afficher le site
```
define('_SCSSPHP_SPIP_CLI_BIN', '/path/to/php /path/to/spip');
```

Par ailleurs, on exclus ainsi la compilation PHP des profilage PHP quand on veut faire une analyse de performance avec le profileur xdebug.

#### Références

* Inspiration http://seenthis.net/messages/199765
* https://seenthis.net/messages/205041
* Documentation de Sass Lang https://sass-lang.com/documentation





