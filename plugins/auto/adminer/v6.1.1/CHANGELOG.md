# Changelog du plugin Adminer

## 6.1.0 - 2025-08-25

### Added

- #10: Ajout d'un pipeline `adminer_plugins` pour ajouter ses propres plugins. Cf https://contrib.spip.net/Adminer#Ajout-de-plugins

## 6.0.0 - 2025-08-18

### Security

- Bloquer les accès directs à Adminer.

### Added

- #8: Une autorisation surchargeable qui contrôle l'accès à adminer, en plus du menu

### Changed 

- Adminer version 5.3.0 (Suppression du support de SQLite 2 & Mysql 4)
- Ajout du plugin dark-switcher
- Ne pas permettre de démarrer Adminer en dehors de SPIP

### Fixed

- #9 (temporaire) Chercher d’abord la constante ROOT plutôt que app(), pour le démarrage en SPIP 4.4

### Removed

- Support de SQLite2 & Mysql 4

## 5.0.0 — 2025-02-25

### Changed

- Compatible SPIP 4.2 minimum
- Adminer version 4.17.1
