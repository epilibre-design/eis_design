# YAML

![YAML](yaml.svg)

Convertir une chaine YAML en structure PHP, et l'inverse. Un format pensé pour être écrit à la main, là
où JSON ne s'y prête pas — d'où son emploi par nombre de plugins pour leurs fichiers de déclaration :
noisettes, saisies, formulaires, descriptions de collections.

**SPIP n'embarque pas YAML nativement.** Ce plugin en est le seul fournisseur, et le noyau lui-même
attend une fonction `yaml_decode()` de qui veut la fournir.

- **Préfixe** : `yaml`
- **Licence** : MIT
- **Auteurs** : Fil, Eric Lupinacci
- **Librairie** : [symfony/yaml](https://symfony.com/doc/current/components/yaml.html), embarquée

Le numéro de version et la compatibilité SPIP font foi dans [`paquet.xml`](paquet.xml), et les
modifications de chaque version sont consignées dans le [journal des modifications](CHANGELOG.md).

## Utiliser le plugin

```php
include_spip('inc/yaml');

$tableau = yaml_decode_file(find_in_path('mon_plugin/config.yaml'));
$chaine  = yaml_encode($tableau);
```

Un fichier YAML peut en inclure un autre, ce que ne fait pas le format lui-même. Une valeur de la forme
`inclure:chemin/fichier.yaml` est remplacée par le contenu décodé du fichier désigné, cherché dans tout
le chemin SPIP — un plugin peut donc surcharger le fichier d'un autre. Le traitement est récursif, et il
faut le demander :

```php
$tableau = yaml_decode_file($fichier, ['include' => true]);
```

**Aucune exception ne sort du plugin.** Un fichier absent ou illisible rend `[]`, un YAML malformé rend
`false`, et l'erreur est journalisée dans le fichier de log `yaml`. Comme aucune valeur de retour ne peut
signaler l'échec de façon fiable — un document YAML valide peut valoir `false`, `null` ou `[]` —, la
cause est offerte par un argument passé par référence :

```php
$tableau = yaml_decode_file($fichier, ['include' => true], $erreur);
if ($erreur) {
    // chemin vide, fichier introuvable ou illisible, YAML malformé
}
```

Dans un squelette, la boucle `DATA` lit une source YAML sans avoir à nommer le plugin :

```spip
<BOUCLE_config(DATA){source yaml, #CHEMIN{mon_plugin/config.yaml}}>
```

La présentation générale et les exemples de mise en œuvre sont publiés sur SPIP-Contrib :
<https://contrib.spip.net/Le-plugin-YAML-v2>

## Comprendre la conception

Le document de conception décrit les deux idées du plugin — une librairie appelée directement, et
l'inclusion d'un fichier dans un autre —, l'API, les options et le contrat d'erreur :
[docs/guide-conception-yaml.md](docs/guide-conception-yaml.md)

## Contribuer

- **Tests de non-régression** : la page de démonstration du plugin, vue « Cas limites ». Elle rejoue à
  chaque affichage le comportement attendu de l'API — chemins qui ne mènent à rien, documents valides qui
  ne sont pas des tableaux, échecs d'analyse, inclusions, encodage — et conclut par un verdict. Une ligne
  rouge est une régression.
- Dépôt : <https://git.spip.net/spip-contrib-extensions/yaml>
- Fiche du plugin : <https://plugins.spip.net/yaml>
