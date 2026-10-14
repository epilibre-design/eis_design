# Changelog

## 3.5.1 - 2026-10-09

### Fixed

- #31 Réparer les crayons sur tables distantes
- #32 Inclusion manquante dans crayons_store
- Analyse de description de champ SQL s’il y a un espace surnumérraire

## 3.5.0 - 2026-10-06

### Security

- crayons_objet_modifier() ne fonctionne que sur les tables principales, et si id est bien numerique
- autoriser_crayonner_dist() utilise le crayon_type non normalisé passé en option
- fournir à autoriser() la liste des champs qu'on veut editer et pas seulement le modele
- distant_table() retourne [$distant, $table, $type] qui ont été normalisés
- sanitizer les noms des colonnes pour les modifs en base
- sanitization SQL
- revision_traduction() quote proprement cles et valeurs du fichier de langue modifié
- verifier le format du fichier qu'on veut upload sur l'action crayons_upload
- si crayons/upload n'a pas été activé, on accepte pas d'upload sur action=crayons_upload
- verifier que les fichiers uploadés comme logo ou vignette sont bien des images
- toujours vérifier préalablement l'existence de la table sur laquelle on veut crayonner
- vérification autoriser(crayonner) sur action_crayons_upload() avec modele=joindredocument que l'on traite spécifiquement dans autoriser_crayonnerchampnoneditable_dist()
- logo_revision() n'accepte d'operer que sur un type d'objet connu de SPIP
- renforcement de `autoriser_crayonner_dist()`
- si la table n'est pas trouvée, l'autorisation retourne false, pas la peine d'aller plus loin
- verifier a minima les droits rapide dans action_crayons_store_dist() comme au moment de l'insertion des crayons dans le public

### Fixed

- la fonction revision_traduction() génère des fichiers de langue au format SPIP 4.1+
- fonction crayons_get_table() utlilise toujours base_trouver_table() et retourne null si rien trouvé

## 3.3.0

### Added

- permettre d'avoir des controleurs et vues personalisés pour les crayons de meta

## 3.2.1 - 2025-02-25

### Fixed

- Chaînes de langue au format SPIP 4.1+

## 3.2.0 - 2025-01-27

### Changed

- #28 Compatibilité SPIP 4.*

## 3.1.3 — 2025-01-10

### Changed

- La fonction `Crayons_preparer_page` est déplacée dans inc `inc/crayons_preparer_page.php` et devient une fonction `_dist`
- #22 Améliorer la chaîne de langue pour activer les crayons dans le privé

### Fixed

- #27 Ne plus utiliser la globale `auteur_session` obsolete


## 3.1.2 — 2023-06-09

### Fixed

- Compatibilité SPIP 4.3

## 3.1.1 — 2023-10-11

### Fixed

- #17 Réparer la couleur de fond des champs des crayons
- Correction d'une coquille CSS

## 3.1.0 — 2023-06-05

### Changed

- #3 Éviter d’activer les crayons au scroll sur mobile
- Upgrade en jQuery 3.7.0 (1.12 avant)
- Le cache JS de crayons va dans `local/cache-js` (et pas `local/` tout court)

### 3.0.0 — 2023-03-15

## Changed

- Compatible SPIP 4.1 minimum
- Compatible SPIP 4.2
