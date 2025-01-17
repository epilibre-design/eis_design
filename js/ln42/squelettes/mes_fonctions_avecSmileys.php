<?php

//
// A noter en cas de changement de dossier squelettes avant spip 1.9
// $GLOBALS['dossier_squelettes'] = 'squelettes';


//
// Filtre pour afficher la date selon le choix de langue
//

function today($site) {
	$t = strftime('%Y-%m-%d', time());
	return nom_jour($t) . ' ' . affdate_base($t, 'entier');
} 

// Ce filtre ajoute la variable mois de la date sur laquelle on l’applique
// dans l’url de la page actuelle et retourne cette url
// il est utilisé lors d'un classement par date


function ajoute_date($date,$var = 'mois') {
  $url = $GLOBALS["clean_link"]->getUrl();
  $mois = $_GET[$var];
  $url = preg_replace('/[?&]'.preg_quote($var).'='.preg_quote($mois).'/','',$url);
  return quote_amp($url.(strpos($url,'?')?'&amp;':'?')."$var=$date");
}




/*
 *   +----------------------------------+
 *    Nom du Filtre :    smileys II
 *   +----------------------------------+
 *    Date : mercredi 14 octobre 2003
 *    Auteur :  BoOz (booz.bloog@laposte.net)
 *   +-------------------------------------+
 *    Fonctions de ce filtre :
 *    Dans un texte, génère automatiquement le smiley 
 *    approprié à la place d'une chaine :nom.
 *    Ce filtre utilise les smileys disponibles dans       
 *    le répertoire smileys/
 *    Exemple d'application :
 *    [(#TEXTE|emoticones)]
 *   +-------------------------------------+ 
 *  
 * Pour toute suggestion, remarque, proposition d'ajout
 * reportez-vous au forum de l'article :
 * http://spip-contrib.net/article261.html
 */



function emoticones($chaine) {

$listimag=array();
$rep1="squelettes/images/smileys/";
$listfich=opendir($rep1);
while ($fich=readdir($listfich))
{ 	if(($fich !='..') and ($fich !='.') and ($fich !='.test'))
	{ 
$nomfich=substr($fich,0,strrpos($fich, "."));
$listimag[$nomfich]="<img ALT=\"smiley\" src=\"squelettes/images/smileys/".$fich."\">";
	}
}


ksort($listimag);
reset($listimag);

while (list($nom,$chem) = each($listimag))
{ 
  $chaine = str_replace(":".$nom, $chem , $chaine);
}

        return $chaine;
}




//
// Convertir les chiffres en lettres – garanti Arno*
// le filtre que je préfère entre tous...
//

function chiffre($unites,$genre=''){
	if ($unites==1 AND $genre=='fem'){$retour="une";}
	if ($unites==1 AND $genre!='fem'){$retour="un";}
	if ($unites==2){$retour="deux";}
	if ($unites==3){$retour="trois";}
	if ($unites==4){$retour="quatre";}
	if ($unites==5){$retour="cinq";}
	if ($unites==6){$retour="six";}
	if ($unites==7){$retour="sept";}
	if ($unites==8){$retour="huit";}
	if ($unites==9){$retour="neuf";}

	return $retour;
}

function chiffre_sans($unites){
	if ($unites==2){$retour="deux";}
	if ($unites==3){$retour="trois";}
	if ($unites==4){$retour="quatre";}
	if ($unites==5){$retour="cinq";}
	if ($unites==6){$retour="six";}
	if ($unites==7){$retour="sept";}
	if ($unites==8){$retour="huit";}
	if ($unites==9){$retour="neuf";}

	return $retour;
}

function chiffre_et($unites, $genre=''){
	if ($unites==1 AND $genre=='fem'){$retour=" et une";}
	if ($unites==1 AND $genre!='fem'){$retour=" et un";}
	if ($unites==2){$retour="-deux";}
	if ($unites==3){$retour="-trois";}
	if ($unites==4){$retour="-quatre";}
	if ($unites==5){$retour="-cinq";}
	if ($unites==6){$retour="-six";}
	if ($unites==7){$retour="-sept";}
	if ($unites==8){$retour="-huit";}
	if ($unites==9){$retour="-neuf";}

	return $retour;
}

function chiffre_dix($unites){
	if ($unites==0){$retour="dix";}
	if ($unites==1){$retour="onze";}
	if ($unites==2){$retour="douze";}
	if ($unites==3){$retour="treize";}
	if ($unites==4){$retour="quatorze";}
	if ($unites==5){$retour="quinze";}
	if ($unites==6){$retour="seize";}
	if ($unites==7){$retour="dix-sept";}
	if ($unites==8){$retour="dix-huit";}
	if ($unites==9){$retour="dix-neuf";}

	return $retour;
}

function chiffre_et_dix($unites){
	if ($unites==0){$retour="-dix";}
	if ($unites==1){$retour=" et onze";}
	if ($unites==2){$retour="-douze";}
	if ($unites==3){$retour="-treize";}
	if ($unites==4){$retour="-quatorze";}
	if ($unites==5){$retour="-quinze";}
	if ($unites==6){$retour="-seize";}
	if ($unites==7){$retour="-dix-sept";}
	if ($unites==8){$retour="-dix-huit";}
	if ($unites==9){$retour="-dix-neuf";}

	return $retour;
}


function dizaines($dizaines,$unites,$genre=''){
	
	if ($dizaines==0){
		$retour=chiffre($unites,$genre);
	}		
	if ($dizaines==1){
		$retour=chiffre_dix($unites);
	}		
	if ($dizaines==2){
		$retour="vingt".chiffre_et($unites,$genre);
	}		
	if ($dizaines==3){
		$retour="trente".chiffre_et($unites,$genre);
	}		
	if ($dizaines==4){
		$retour="quarante".chiffre_et($unites,$genre);
	}		
	if ($dizaines==5){
		$retour="cinquante".chiffre_et($unites,$genre);
	}		
	if ($dizaines==6){
		$retour="soixante".chiffre_et($unites,$genre);
	}		
	if ($dizaines==7){
		$retour="soixante".chiffre_et_dix($unites);
	}		
	if ($dizaines==8 & $unites>0){
		$retour="quatre-vingt-".chiffre($unites,$genre);
	}		
	if ($dizaines==8 & $unites==0){
		$retour="quatre-vingts";
	}		
	if ($dizaines==9){
		$retour="quatre-vingt-".chiffre_dix($unites);
	}		

	return $retour;

}





function par3($lavaleur,$demille,$genre='masc',$type=''){
	
	if (strlen($lavaleur)==1){$lavaleur="00".$lavaleur;}
	if (strlen($lavaleur)==2){$lavaleur="0".$lavaleur;}
	
	$centaines=substr($lavaleur,0,1);
	$dizaines=substr($lavaleur,1,1);
	$unites=substr($lavaleur,2,1);	

	if ($centaines>1){
		$cent="cents";
	}

	if ($demille=="mille"){
		$cent="cent";
	}
	
	if ($dizaines+$unites>0){
		$cent="cent";
	}
	
	if (($demille=="million" OR $demille=="milliard") AND $centaines+$dizaines+$unites>1){
		$demille=$demille."s";
	}	
	
	if ($centaines+$dizaines+$unites>0){
		$demille=" ".$demille;
	}else{
		$demille="";
	}
	
	if ($centaines>0){
		$retour=chiffre_sans($centaines)." $cent ".dizaines($dizaines,$unites,$genre);
	}else{
		$retour=dizaines($dizaines,$unites,$genre);
	}
	
	if (trim($retour)=="un" & trim($demille)=="mille"){
		$retour="";
	}
	if (trim($retour)=="un" AND $type=='date'){
		$retour="premier";
		if($genre=='fem') $retour="premi&egrave;re";
	}

	if($genre=='fem' AND $retour=="un") $retour="une";


	return $retour.$demille;
}



function en_lettres($lavaleur,$genre='masc',$type=''){
	
	$lavaleur=strrev($lavaleur);

	$unites=par3(strrev(substr($lavaleur,0,3)),"","$genre","$type");
	$milliers=par3(strrev(substr($lavaleur,3,3)),"mille");
	$millions=par3(strrev(substr($lavaleur,6,3)),"million");
	$milliards=par3(strrev(substr($lavaleur,9,3)),"milliard");

	return trim("$milliards $millions $milliers $unites");
 
}

function jour_en_lettres($lavaleur){
	return en_lettres($lavaleur,'','date');
}

function en_lettres_fem($lavaleur){
	return en_lettres($lavaleur,'fem','');
}




function enlettres($lenombre,$unite='',$unites='',$entre='',$apres='',$apress=''){


	$lenombre=ereg_replace(",",".",$lenombre);
	$lenombre=explode(".",$lenombre);
	
	$entier=$lenombre[0];
	
	if ($entier>1 & strlen($unite)>0){
		$unite=$unites;
	}
	
	$virgule=$lenombre[1];
	if ($virgule>1 & strlen($apres)>0){
		$apres=$apress;
	}
	
	if ($entier==0 OR $virgule==0){
		$entre="";
	}


	if ($entier==0){
		$unite="";
	}
	$entier=en_lettres($entier);
	
	if ($virgule==0){
		$apres="";
	}
	$virgule=en_lettres($virgule);
	
	
	return trim("$entier $unite $entre $virgule $apres");
}

?>
