# Changelog

## v5.0.5 - 2024-04-29

## Fixed

- #87 Pouvoir créer un événement, même si l'article par défaut n'existe plus


## 5.0.4 - 2023-12-12

### Fixed

- Vue de la saisie évènement : afficher l'évènement quelque soit son statut


## 5.0.3 - 2023-11-02

### Fixed

- Passer les `#URL_EVENEMENT` en `&` au lieu de `&amp;` si besoin (json)

## 5.0.2 - 2023-10-11

### Fixed

- saisies#329 Retour correct du pipeline `saisies_afficher_si_saisies`
- #79 Si l'option "Désactiver mais poster" est cochée, n'afficher que l'évènement dont l'id est passée comme valeur en paramètre.
- #76 Corriger un bug avec `agenda_moisdecal()` sur certaines dates
- #81 Rediriger correctement sur la page public d'evenement en cas de non configuration du plugin

## 5.0.1 - 2023-07-10

### Fixed

- Différents warnings et erreurs en PHP 8
- Refaire fonctionner l'outils de migration

### Removed

- Images PNG
## 5.0.0 - 2023-05-18

### Changed

- Compatibilté SPIP4.2 et plus

## 4.5.4 - 2022-09-08

### Added

- Constructeur de saisie : documentation des options de dev

### Changed

- Constructeur de saisie : `afficher_si` dans onglet à part (cf. Saisies v4.4.0)

### Fixed

- #59 Rétablir le fonctionnement de `agenda_moisdecal` cassé par 5ca177f, tout en évitant les indéfinies et idem sur agenda_jourdecal
- #58 Include manquant lors d'une edition depuis les crayons
- Correction d'un coquille mineure dans une chaîne de langue
- Mise à jour d'agenda depuis vielle version de SPIP (3.2 comprise)
- `#ENV{claire}` n'est plus valable en SPIP 4
- Le plugin étant encore compatible SPIP 3.2, on ne monte finalement pas le minimum pour saisies.
- Corriger un warning si on ne précise pas le type de choix sur la saisie evenement
