# Changelog

## 3.3.0 - non publiée

### Added

- Argument `$erreur` passé par référence sur les fonctions de décodage : il reçoit le message d'échec,
  qu'aucune valeur de retour ne peut signaler, un document YAML valide pouvant valoir `false` ou `null`
- `yaml_to_array()` : implémentation unique du décodage pour la boucle `DATA`, dont `inc_yaml_to_array()`
  et `inc_yaml_to_array_dist()` ne sont plus que des adaptateurs
- `README.md`, et un `.gitattributes` qui exclut `docs/` et `phpcs.xml.dist` de l'archive distribuée
- PHPDoc complétée sur toutes les fonctions : arguments, valeurs de retour, `@api` ou `@internal`

### Changed

- **Compatibilité étendue à SPIP 5.0** : `[4.1.0;5.*]`. Une seule base sert les deux branches, sans code
  conditionnel ; `symfony/yaml` 5.4 fonctionne sous PHP 8.4 sans émettre d'avis
- Démonstration dépoussiérée : la comparaison entre librairies disparait, il n'en reste qu'une. Elle
  devient la suite de tests du plugin — une page **Cas limites** qui joue 13 cas à chaque affichage et
  conclut par un verdict, un scan qui signale les fichiers en échec, une boucle `DATA` sur un fichier local

### Removed

- `prive/`, dont le logo PNG que plus personne n'utilisait ; le logo SVG passe à la 
racine en `yaml.svg`
- Démonstration : le menu des librairies, le tableau comparatif et les fonctions qui le servaient

### Fixed

- `yaml_decode_file()` ne provoque plus d'erreur fatale sur un chemin vide ou `false`, ce que rend
  `find_in_path()` quand le fichier est absent
- `yaml_decode_file()` décode enfin un fichier dont le contenu se réduit à `0`
- `inc_yaml_to_array_dist()` rendait `null` et un avis PHP sur un YAML non tabulaire, et divergeait de
  `inc_yaml_to_array()` : les deux rendent désormais toujours un tableau
- Déclaration de `inc_yaml_to_array_dist()` gardée : inclure `inc/yaml_to_array.php` quand l'itérateur
  `DATA` était chargé était une erreur fatale de redéclaration
- `symfony_yaml_decode()` : valeur par défaut erronée sur `$input`, vestige d'une signature de 2018
- Démonstration : l'image du bandeau appelait `yaml.png`, un fichier qui n'a jamais existé ; l'option
  `library` était lue sans garde, d'où un avis PHP 8 ; la page de boucle `DATA` était orpheline et
  interrogeait une URL externe

## 3.2.1 - 2026-08-30

### Fixed

- Correction et simplification de la gestion des inclusions
- Mise à jour lib symfony/yaml en 5.4.53

## 3.2.0 - 2025-09-02

### Removed

- Librairies yaml spyc & libyaml et des tests afférents (symfony/yaml utilisée par défaut 
est la référence maintenant)

## 3.1.2 - 2024-10-05

### Fixed

- Mise à jour lib symfony/yaml en 5.4.44 (corrige des deprecated en PHP 8.4 notamment)

## 3.1.1 - 2024-10-03

### Fixed

- spip-contrib-extensions/saisies#108 Préserver les clés numériques dans `yaml_charger_inclusions()`
