# Plugin Autoupdate

Mises à jour automatiques de SPIP (core et plugins du core) via les patchs publiés sur
[spip_loader.api](https://www.spip.net/spip_loader.api/3).

Pour chaque nouvelle version, un patch couvre les 5 versions mineures précédentes
(ex. 4.4.18 → 4.4.23 en un seul zip).

## Configuration

Page de configuration du plugin :

* **Mode** — mise à jour automatique ou déclenchement manuel (manuel par défaut).
* **Fichiers divergents** — appliquer même si des fichiers du core diffèrent de la version officielle (désactivé par défaut). Utile surtout après une install via `git` (par Composer ou [checkout](https://git.spip.net/spip-contrib-outils/checkout/)), ou après une mise à jour interrompue.
* **Rapport auto** — envoyer un rapport de debug (à plugins.spip.net) après chaque mise à jour (désactivé par défaut).

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

Sur la page de configuration, un rollback vers la version précédente est proposé
**si** un patch de rollback a été publié par le core.

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

Envoi automatique (option) ou manuel après une mise à jour (réussie ou non), vers
[https://plugins.spip.net/report.api](https://plugins.spip.net/report.api).

Données collectées : adresse du site, versions SPIP/PHP, fichiers datés du patch,
`manifest-translated.json`, logs du patch (`errors.txt`, `processing.txt`, etc.),
`progress-*.txt`, `tmp/log/autoupdate.log`, `phpinfo()`.

Pas d’information personnelle en principe, sauf éventuellement l’IP de la personne
ayant déclenché une MAJ manuelle.

## Documentation technique

* [Fonctionnement interne](docs/fonctionnement.md) — pipeline, `spip-update.php`, finalisation
* [Format des patchs](docs/format-patch.md) — API, zip, manifest

## TODO

* Tester sur des configurations diverses avant une éventuelle intégration au core
* Anti-retry : limiter les retentatives après échec avant de notifier le webmestre
