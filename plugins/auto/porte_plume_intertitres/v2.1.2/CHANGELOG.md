# Changelog

## 2.1.2 - 2025-02-27

### Fixed

- Chaînes de langue au format SPIP 4.1+

## 2.1.1 - 2024-07-05

### Changed

- Compatiblité SPIP 4.2 minimum

## 2.1.0 - 2024-07-05

### Changed

- Compatiblité SPIP 4.*

## 2.0.7 - 2023-06-09

### Added

- Compatiblité SPIP 4.2

## 2.0.4 - 2022-12-17

### Added

- Ajout d'un attribut '{raz}' qui permet de remettre à zéro la numérotation des titres : cette nouvelle fonction permet de gérer l'affichage de plusieurs champs avec intertitres numérotés.

### Changed

- Action qualité sur le code : nettoyage, typage des fonctions, PHPDoc

## 2.0.0 - 2022-05-30

### Changed

- Le niveau par défaut passe de `h3` à `h2`, comme dans SPIP
- #2 Parler de "titres numérotés" plutôt que de "titre de type référence"

### Removed

- Compatibilité avec les version de SPIP < 4.0
- Icones PNG, ne reste que les SVG

## 1.1.1 - 2017-10-17

### Added

- Mise en place des niveau supérieurs a 5 et aller jusqu'au niveau 6 ou 7 suivant le niveau défini comme départ des intertitres spip (h3 par defaut).
- Pour mieux sensibiliser le rédacteur ou la rédactrice ;
on affiche les icones de la barre outil en fonction du niveau defini par la globale spip_debut_intertitre
(h2,h3,h4,h5,h6). Dans le cas de spip par defaut démarrant a h3 on signale par un h7 en rouge converti en `div.h7`

### Changed

- Raccourcissement de label

## 1.1.0 - 2017-10-16

### Added

- Possibilité de masquer les titres numérotés

## 1.0.8 - 2016-08-03,

### Changed

- Optimisation

## 1.0.7 - 2016-07-31

### Fixed

- correctif sur les intertitres standards qui n'étaient plus gérés correctement

## 1.0.6 - 2016-07-31

### Added

-   prise en charge dans les attributs de propriétées libres `propriete="valeur"`.  Ex `{.class .block--modifier #ID data-appear="left" itemprop="name"}.

## 1.0.5 - 2016-07-31

### Added

- Personnaliser les .class
- Gestion d'attributs additionnels aux titres via le raccourci inspiré de la syntaxe markdown `{.class .block--modifier #ID}`.


## 1.0.4 - 2016-07-29

### Changed

- Limiter le niveau et passer sur un div a h6

## 1.0.3 - 2016-07-29

### Changed

- Report de la gestion du niveau sur les titres numérotés

## 1.0.2 - 2016-07-29

### Changed

- Nettoyage divers de code

## 1.0.1 - 2016-07-29

### Added

- Fonction de callback sur les wheels des intertitres, gérant
