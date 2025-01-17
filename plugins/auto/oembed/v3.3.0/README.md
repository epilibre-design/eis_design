# Oembed

Ce plugin pour SPIP permet d’insérer un contenu HTML à partir d’une simple URL, dans les textes des contenus éditoriaux (comme les articles).

Par exemple insérer une vidéo dans le site à partir de l’URL d’une vidéo YouTube.

Voir la documentation complète : https://contrib.spip.net/4407

## Fonctionnement

### Insertion en raccourci dans le texte

Il y a 2 syntaxes valides

- l’URL, entourée de chevrons `<` et `>`
```
<https://...>
```
- l’URL seule, entourée de sauts de ligne
```

https://...

```

Dans les 2 cas l'URL, si elle correspond à un fournisseur oEmbed, est remplacée par le medias associé

### Insertion en document joint

Il est possible aussi d'insérer l'oEmbed en document joint, comme un document distant.
Lors de l'insertion, le plugin reconnait l'URL oEmbed et récupère les informations pour catégoriser le document
dans le bon type de media et l'enregistrer directement le media associé dans les documents.


## Migration depuis le plugin Videos

Le plugin propose à partir de la v3.2.0 un chemin de migration depuis le plugin videos qui n'est plus maintenu :
- la commande `spip oembed:migrer:videos-dist` permet de migrer les documents joints créés par le plugin vidéos
en documents joints de type oEmbed, à condition que le media soit toujours disponible en ligne
- le modèle `<videoXX>` qui était utilisé par le plugin videos est pris en charge également
