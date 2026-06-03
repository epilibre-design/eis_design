# Passage de saisies 5.0 à 6.0

## Modification de la saisie `date`

La saisie `date` s'appuie désormais sur l'attribut `type='date'` de la norme HTML 5, et non plus sur un picker spipien en javascript.

Ceci à une conséquence importante : alors que le navigateur renvoyait auparavant au serveur PHP la date au format `JJ-MM-AAAA`, il le renvoie désormais format `AAAA-MM-JJ`.

Comment faire avec cela ?

### Pour les saisies déclarée en PHP ou en balise `#SAISIE`.

Il convient d'appliquer la normalisation sur le champ date en passant la bonne option d'entrée.

Si déclaration en PHP :
```php
…,
[
    'saisie' => 'date',
    'verifier' => [
        [
            'type' => 'date',
            'options' => [
                'format' => 'amj',
                'normaliser' => 'date_ou_datetime'
            ]
        ]
    ]
],…
```

Si `#SAISIE` :

```php
$verifier = charger_fonctions('verifier', 'inc/');
$erreur = $verifier($valeur, 'date', ['format' => 'amj', 'normaliser' => 'date_ou_datetime'], $valeur_normalisee);
// Désormais, se baser sur $valeur_normalisee pour la suite du traitement
```

Pour permettre d'avoir du code compatible avec plusieurs branches de saisies, on peut aussi ne pas mettre l'option `format` en utilisant la version 3.6.1 du plugin `verifier`.

### Pour les saisies déclarées avec un constructeur de formulaire (Formidable, champ extra interface, etc).

Si la saisie a été déclarée après fin janvier 2012, il n'y a rien à faire.

Si la saisie a été déclaré avant fin janvier 2012, il faut la modifier pour ajouter la normalisation. A noter qu'il n'y a pas de possibilité de préciser `format`, mais que le plugin `verifier`, dans sa dernière version, fera l'autodetection.

Pour le plugin `formidable`, la normalisation est ajoutée automatiquement en mettant à jour le plugin.

## Attribut `id`

Jusqu'à la v5 (inclue) du plugin `saisies`, lorsqu'on passait explicitement une option `id` à une saisie, l'attribut `id` était malgrè tout préfixé par `champ_`.

À partir de la v6 du plugin :

- si aucune option `id` n'est passé, les `id` sont construits sous la forme `champ_` + nom de la saisie ; `champ` peut éventuellement être remplacée par la valeur de l'option globale `prefixe_id`. Pour la saisie `explication`, `champ` est remplacé par `explication` sauf en cas de `prefixe_id`
- sinon, l'`id` correspond à l'option `id`, éventuellement préfixée de l'option globale `prefixe_id`, séparée par un `_`

Il faut donc potentiellement adapter les styles css et les scripts JS.

A noter que pour les plugins qui proposent leurs propres saisies, il peut être nécessaire de faire des adaptations. Il suffit d'utiliser `#ENV{id}` dans le squelette de la saisie concernée à la place de construire manuellement l'id.

## Activation systématique des attributs HTML5

Avec saisies 6, les attributs HTML5 sont systématiquement présents :

- tous les navigateurs récents les interprètes
- cela ne pose pas problème aux anciens
- SPIP cessera de supporter les tests #HTML5 en 5.0

## Modification de `saisies_modifier()`

Comme annoncé préalablement en 5.0, l'argument `nouveau_type_saisie` lorsqu'on utilise `saisie_modifier` doit être placée à la racine de `$modifs`

### Avant

```php
$saisies = saisies_modifier($saisies, 'xx', ['options' => ['nouveau_type_saisie' => 'xx']]);
```

### Après


```php
$saisies = saisies_modifier($saisies, 'xx', ['nouveau_type_saisie' => 'xx']);
```
## Suppression de fonctionnalités

### Saisie `pays`


#423 La saisie `pays` sur la table `geo_pays` du plugin `geographie` est supprimée ; utiliser à la place la saisie `geo_pays` du plugin `geographie`. A noter que le plugin `pays` fournit aussi une saisie `pays` prenant le pas sur celle qui était livrée avec le plugin `saisie`

### Balise `#DIV`

#### Avant

```
<[(#DIV|sinon{li})>`
```

#### Après

```
<div>
```


### Filtre `saisie_balise_structure_formulaire`

#### Avant

```
<[(#VAL{li}|saisie_balise_structure_formulaire)]>
```

#### Après

````
<div>
````

### `saisies_normaliser_disable_choix()`

À remplacer par `saisies_normaliser_liste_choix()`.

### `saisies_verifier_gel_saisie()`

#### Avant

```
if (saisies_verifier_gel_saisie($saisie))
```

### Après


```
if (saisies_saisie_est_gelee($saisie))
```

### Saisies `selection`, options `blacklist` et `whitelist`

Pour la saisie `selection`:

- l'option `blacklist` doit être remplacée par `excludelist`
- l'option `whitelist` doit être remplacée par `includelist`


## Modification de `saisies_modifier()`

Comme annoncé préalablement en 5.0, l'argument `nouveau_type_saisie` lorsqu'on utilise `saisie_modifier` doit être placée à la racine de `$modifs`

### Avant

```php
$saisies = saisies_modifier($saisies, 'xx', ['options' => ['nouveau_type_saisie' => 'xx']]);
```

### Après


```php
$saisies = saisies_modifier($saisies, 'xx', ['nouveau_type_saisie' => 'xx']);
```
