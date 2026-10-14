# Plugin Autoupdate

Mises à jour automatiques de SPIP (core et plugins du core) via les patchs publiés sur
[spip_loader.api](https://www.spip.net/spip_loader.api/3).

Pour chaque nouvelle version, un patch couvre les 5 versions mineures précédentes
(ex. 4.4.18 → 4.4.23 en un seul zip).

## Configuration

Page de configuration du plugin :

* **Mode** — mise à jour automatique ou déclenchement manuel (manuel par défaut).
* **Fichiers divergents** — appliquer même si des fichiers du core diffèrent de la version officielle (désactivé par défaut). Utile surtout après une install via `git` (par Composer ou [checkout](https://git.spip.net/spip-contrib-outils/checkout/)), ou après une mise à jour interrompue.
* **Rapport auto** — envoyer un rapport de debug (à plugins.spip.net) après chaque mise à jour réussie ou échouée (désactivé par défaut).

## Utilisation

Compatible avec une install via zip / FTP ou [spip_loader](https://git.spip.net/spip-contrib-outils/spip_loader).

Avec `checkout`, passer `--no-dev` (sinon des fichiers `vendor/` peuvent différer),
ou activer l’option « fichiers divergents ». Le plugin ne modifie pas les dossiers `.git` :
les fichiers mis à jour apparaîtront en différence git.

### Périmètre

Peuvent être mis à jour les fichiers fournis dans l’archive de release :

* racine de SPIP, `ecrire/`, `prive/`, `squelettes-dist/`
* `config/ecran_securite.php` et `config/spip/`
* `vendor/` (composer)
* plugins-dist, y compris s’ils sont rangés dans un sous-dossier de `plugins-dist/`,
  ou dans `plugins/` (dans ce dernier cas, uniquement s’ils sont actifs)

### CLI

Via [spip-cli](https://git.spip.net/spip-contrib-outils/spip-cli/)
```
$ spip autoupdate:update
Mise à jour de SPIP 4.4.20 vers 4.4.23
...
 ✔ Status : finie avec succès
```

```
$ spip autoupdate:update
 ! SPIP 4.4.23 : aucune mise à jour disponible
```

## Rollback

Le rollback ne concerne **que les fichiers**. Il ne revient pas en arrière sur la version de la base.
Si la MAJ a modifié le schéma SQL, SPIP peut mal fonctionner après un retour arrière des fichiers.

### Interface

Sur la page de configuration, les rollbacks disponibles depuis la version installée
sont proposés (API `spip_loader.api/3/{version}`), **si** des patches de rollback
ont été publiés par le core (fenêtre des 5 dernières mineures).

Passer en mode manuel avant de rollback, sinon une mise à jour automatique peut immédiatement
réappliquer la version récente.

### CLI

```
$ spip autoupdate:rollback --to=4.4.20
Rollback de SPIP 4.4.23 vers 4.4.20
...
 ✔ Status : finie avec succès
```

```
$ spip autoupdate:rollback --to=4.4.15
 ! SPIP 4.4.23 : aucun patch disponible pour rollback vers 4.4.15
```

## Rapports de debug

Envoi **opt-in** (option ou bouton manuel) après une mise à jour, vers
[https://plugins.spip.net/report.api](https://plugins.spip.net/report.api),
pour aider au débogage des cas qui échouent selon les configurations.

Contenu typique du payload :

* URL du site (`adresse_site`), versions SPIP / PHP, hash du patch
* liste des fichiers du répertoire de patch (chemins relatifs à la racine du site)
* `manifest-translated.json`, étapes datées, statut
* extraits de logs du patch (`errors`, `processing`, `progress`) en tableaux de lignes
* fin de `tmp/log/autoupdate.log` (tronquée, tableau de lignes), avec emails et chemins absolus retirés
* extrait `phpinfo` : sections General, Configuration, Modules uniquement

N’est **pas** inclus : sections Environment / Variable de `phpinfo` (`$_ENV`, `$_SERVER`,
cookies, etc.), ni les adresses email éventuellement présentes dans les logs
(remplacées par `[email]`).

L’URL du site identifie l’installation ; ce n’est pas un contact personnel.
Un aperçu du rapport peut être consulté dans l’espace privé avant envoi.

## Documentation technique

* [Fonctionnement interne](docs/fonctionnement.md) — pipeline, `spip-update.php`, finalisation
* [Format des patchs](docs/format-patch.md) — API, zip, manifest

## TODO

* Tester sur des configurations diverses avant une éventuelle intégration au core
* Anti-retry : limiter les retentatives après échec avant de notifier le webmestre
