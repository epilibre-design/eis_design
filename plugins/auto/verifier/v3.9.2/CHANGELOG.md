# Changelog

## 3.9.2 - 2026-04-14

### Fixed

- #42 : pas de texte alternatif dans la CSS privée si le plugin Yaml n'est actif 

## 3.9.1 - 2026-03-06

### Fixed

- #41 autoriser le caractère + dans la vérif email strict (#41)


## 3.9.0 - 2026-01-24

### Added

- Option pour n'autoriser qu'une seule adresse lorsqu'on demande un email

### Fixed

- Ne pas autoriser `.@` dans une vérification d'email en mode strict
- Ne pas autoriser `..` dans une vérification d'email en mode strict
- Ne pas autoriser plus de 64 charactères avant le @ dans une vérification d'email en mode strict
## 3.8.0 - 2025-11-28

### Added

- Constructeur de formulaire: pour le téléphone, proposer aussi de vérifier le
    code pays

## 3.7.0 - 2025-11-03

### Added

- vérification `id_document` : options `media` et/ou `extension` permettent de préciser des contraintes supplémentaires

## 3.6.1 - 2025-10-14

### Fixed

- spip-contrib-extensions/saisies#495 la normalisation en datetime d'une saisie `date` pour `saisies` v6 doit renvoyer 0000-00-00 comme heure

## 3.6.0 - 2025-09-01

### Fixed

- fix: `normaliser_date_datetime()` ne doit pas croire que cela a été normalisé si on recoit une valeur `yy-mm-dd` mais qu'on attend en plus une heure

### Change

- Si on ne précise pas le format d'entrée des dates, on tente une autodetection, et à défaut, on considère que c'est jma


## 3.5.0 - 2025-06-05

### Added

- spip-contrib-extensions/formidable#293 Option `objets_spip` pour la vérfication `url`

### Changed

- Bibliothèque `inc/is_email` en 3.0.7
- Ergonomie en constructeur de formulaire du choix d'un protocole exacte pour la vérification d'url

### Fixed

- Ne pas déclarer à tord les emails contenant une virgule comme invalide

## 3.4.0 - 2025-05-14

### Fixed

- #30 Pour la vérification email, accepter `.space` et `.travel` et autres extensions jusqu'à 18 caractères

## 3.3.1 - 2025-02-23
### Added

- #37 L'option `image_web` de la vérification `fichiers` s'appuie sur les types d'image web déclarés par SPIP

### Fixed

- #38 Pour avoir la documentation, nécessité d'avoir le plugin `saisies` activé
- Utiliser `_IMG_MAX_SIZE` pour toutes les images web vectoriel
- #36 Corriger le mode complet de la vérification URL (mauvaise regex)
- Plus de catégorie dans le paquet

## 3.3.0 - 2024-10-14
### Added

- La normalisation `date_ou_datetime` s'appuie en priorité sur la définition du type sql associée à la saisie en entrée, ce qui permet de faciliter la config en mode "champs extras interface"

### Removed

- Compatibilité SPIP < 4.1
## 3.2.0 - 2024-10-01

### Added

- spip-contrib-extensions/champs_extras_core#25 Avoir une normalisation `date_ou_datetime` selon ce qui est reçue en entrée
## 3.1.2 - 2024-06-12


### Fixed

- #34 Éviter notice en vérifiant un telephone quand pays n'est pas défini dans les options

## 3.1.1 - 2023-06-04

### Fixed

- #32 Trimmer la valeur reçue quand on vérifie la disponibilité d'un email

## 3.1.0 - 2023-04-02

### Added

- #16 Pouvoir vérifier que deux champs ont des valeurs (ou éventuellement des types) différentes
- Compatiblité SPIP 4.2

