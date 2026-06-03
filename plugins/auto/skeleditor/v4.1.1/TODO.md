## TODO plugin skel_editor

- [ ] Gestion des fichiers scss
    - [-] Trouver le fichier source depuis un fichier "cssifié"
            Une fois le fichier passé par la balise `#CSS` on a plus de trace du chemin
            pour le moment/test on extrapole juste sur un find_in_path sur le dossier `css/`
            - [ ] Trouver le chemin d'un fichier qui est passé par un preprocss ?
                  en fait meme en utilisant la class scssphp->getParsedFiles() vu que on passe par le contenu du fichier
                  parssé par le filtre/balise on a pas le chemin.
                  - [?] pourquoi on met pas le path dans le nom du fichier ou dans l'arbo du cache ?  
- [X] Rétablir la rechercher/remplacer
      - [ ] Surlignage de l'item actif quand on utilise cmd+g (suivant)
- [X] Changer le theme, ou les addons utilisés
      Via le fichier addons.js
- [X] update de codemirror
- [X] support scss
- [X] ajout de emmet
  - [ ] ajout de snippets
        https://github.com/emmetio/codemirror-plugin/blob/master/example.html
        https://github.com/emmetio/config
- [X] fullscreen editing
- [X] match tags & brakets
- [X] code folding


ajout de fonctionnalites:
----------------------------
- telecharger l'ensemble du squelette en .zip ?
- documentation des boucles ?
- preview "live" ?

des commentaires ?
https://contrib.spip.net/Plugin-editeur-de-squelettes-Skel


---------------------------------------------
