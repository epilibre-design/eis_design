/*----------------------------------------------------------------------------\
|                       LiveScroll (c) @rthur 2004                            |
| http://www.celeonet.fr - L'hébergement PHP/MySQL professionnel de référence |
|           Utilisation libre sous réserve de conserver ces 3 lignes          |
|-----------------------------------------------------------------------------|
|   utilisation : <a href="javascript:LiveScroll('id1');">Montrer/cacher</a>  |
|   <div id="id1" style="display: none; position: relative;">texte</div>      |
|-----------------------------------------------------------------------------|
|       Important:                                                            |
|                Pensez à modifier l'ID du div et du javascript               |
|           si vous utilisez plusieurs fois ce script dans votre page         |
|                                                                             |
\----------------------------------------------------------------------------*/

function LiveScroll(id_div) {
        if (id_div != '') action_div(id_div);
}

function action_div(id_div) {
        var item = null;
        if (document.getElementById) {
                item = document.getElementById(id_div);
        } else if (document.all){
                item = document.all[id_div];
        } else if (document.layers){
                item = document.layers[id_div];
        }
        
        if (!item) { // rien à faire
        } else if (item.style) {
                if (item.style.display == "none") {
                        item.style.display = "";
                } else {
                        item.style.display = "none";
                }
        } else {
                item.visibility = "show";
        }
}

