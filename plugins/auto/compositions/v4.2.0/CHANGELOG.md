# Changelog

## 4.2.0 - 2025-12-16

### Chore

- fichiers de langue au nouveau format

## 4.1.0 - 2024-07-05

### Changed

- Compatibilité 4.*

## 4.0.2 - 2024-06-17

### Fixed 

- vérifier aussi qu'on a bien un champ id_trad dans la table, sinon on fait une erreur SQL pour rien
- éviter une erreur fatale en PHP8 lors de la création d'un objet sans traduction

## 4.0.1 - 2023-12-15

### Fixed
- cas sans composition, inserer une classe 'sans_composition' (mais pas de classe page toute seule par exemple, c'est trop générique et ça va faire des colisions)

### Changed

- #13 évolution du filtre `composition_class`

## 4.0.0 - 2023-11-21

### Changed

- #12 Compatibilité SPIP 4.1 mini

## Fixed

- #12 Retour de `compositions_verrou_branche()` toujours de type `bool`
