# Changelog

## 4.3.2 - 2026-06-05

### Fixed

- permettre l'édition par crayons de la valeur alternative d'une `selection`

## 4.3.1 - 2026-05-11

### Fixed

- Faire fonctionner les choix alternatifs des saisies

## 4.3.0 - 2025-07-29

### Fixed

- Utiliser l'API de saisies pour trouver le label d'un champ extra (cas des saisies `case` où il vaut mieux utiliser `label_case`)
- Fichiers de langue SPIP 4.1+

### Changed

- Compatible SPIP >= 4.1 seulement
- Logo à la racine

### Removed

- Version png du logo

## 4.2.1 - 2024-10-26

### Fixed

- #27 Ne pas faire disparaitre les `$` ou `\` suivi d'un nombre lors de la réédition d'un champ extra

## 4.1.2 - 2023-03-28

### Added

- #16 Permettre d'indiquer qu'un champ est pris en compte pour le critère `{vu}`
- #19 Compatible SPIP 4.2

### Fixed

- #15 Nécessite le plugin YAML, car utilise l'API PHP de Saisies.

## 4.1.1 - 2022-05-18

### Fixed

- #13 #14 Éviter une erreur fatale lors de l'appel côté public d'un formulaire d'édition d'un objet ayant des champs extras

## 4.1.0 - 2022-05-11

### Added

- Ajout d’un Readme

### Changed

- Compatible avec SPIP 4.1 et PHP 8.1
