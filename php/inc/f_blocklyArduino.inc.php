<?php
/*------------------------------------------
 Fonction : listeFicOpen
-------------------------------------
  
-------------------------------------
 - Entrée :
 - Sortie :
---------------------------------------------*/
function listeFicOpen($userId) {
	global $mysqli;
	global $cheminFichiersXML;

	$tFic=array();
	
	$rqt="SELECT nom,dateHeure FROM fichiers WHERE user LIKE '$userId' ORDER BY dateHeure DESC";
	$res=$mysqli->query($rqt);
	if ($res) {
		while (list($_nom, $_dateHeure)=$res->fetch_row()) {
			$_nomFic="$userId-$_dateHeure.xml";
			if (file_exists('../'.$cheminFichiersXML.'/'.$_nomFic)) $tFic[]=array('nom'=>$_nom,'dateH'=>date("d/m/Y H:i:s",$_dateHeure/1000),'nomFic'=>$_nomFic, 'ts'=>$_dateHeure);
		}
	}
	
	return $tFic;
	
/*	return array(
		array('nom'=>"Fichier 1",'date'=>"03/05/2016"),
		array('nom'=>"o.hacquard-1490050174005.xml",'date'=>"24/12/2016")
	); */
} // fin de fonction listeFicOpen

/*------------------------------------------
 Fonction : presenteListeFichiers
-------------------------------------
  
-------------------------------------
 - Entrée :
 - Sortie :
---------------------------------------------*/
function presenteListeFichiers($listeFic) {
	global $mysqli;
	global $cheminFichiersXML;
	global $uid;
	
	if (count($listeFic)>0) {
		$prems=true;
		foreach ($listeFic as $_tFic) {
			if (!$prems) $chaine.='<br/>';
			$prems=false;
			$_lien='<a class="lienFic" projet="'.$_tFic['nom'].'" href="./blocklyArduino.php?lang=fr&card=arduino_uno&toolbox=toolbox_arduino_all&nom='.$_tFic['nom'].'&url='.$cheminFichiersXML.'/'.$_tFic['nomFic'].'">'.$_tFic['nom'].'</a>';
			$_dateH=$_tFic['dateH'];
			$chaine.='<div class="ligneFicOpen"><span class="nomFicOpen">'.$_lien.'</span><span class="supFic glyphicon glyphicon-trash" alt="Supprimer ce projet" nomF="'.$_tFic['nom'].'"></span><span class="dateHFicOpen">'.$_dateH.'</span></div>';
		}
	} else {
		$chaine='<i>pas de fichier...</i>';
	}
	$chaine.='<script language="JavaScript">
		$(".lienFic").click(function() {
    	//alert($(this).attr("projet"));
    	if (nomProjet!="") return confirm("Un projet est en cours d\'édition...\nEtes-vous sûr de vouloir en charger un autre ?");
    });
    $(".supFic").click(function() {
    	if (confirm("Voulez vous supprimer définitivement le projet \""+$(this).attr("nomF")+"\" ?")) {
	    	$.ajax({
			    type: "GET",
			    url: "./php/listeFic.php?action=supp&nom="+$(this).attr("nomF")+"&uid='.$uid.'",
			    dataType : "text",
			    success : function(result) {
			    	if (result.substring(0,2)=="Ok") {
			    		alert("Projet supprimé !");
			    		$("#btn_open").click();
			    	}
			    	else alert("Problème lors de la suppression du projet !\n\n"+result);
			    },
			    error: function(){
			    	}
				});
			//alert("supp "+$(this).attr("nomF"));
    	}//	else alert("On ne supprime pas "+$(this).attr("nomF"));
    });
    </script>';

	return '<div>'.$chaine.'</div>';
} // fin de fonction presenteListeFichiers

/*------------------------------------------
 Fonction : cadreLoginDeconnexion
-------------------------------------
  
-------------------------------------
 - Entrée :
 - Sortie :
---------------------------------------------*/
function cadreLoginDeconnexion($uid, $top, $right) {
	if ($uid=='admin') $nomUser='Admin';
	else $nomUser=$_SESSION['_user_']['prenom']." ".$_SESSION['_user_']['nom'];
	return '
	<script language="JavaScript">$(function() {
		$("#iconeDeconnexion").mouseenter(function() {
			$(this).toggleClass("iconeDecnx1");
			$(this).toggleClass("iconeDecnx2");
		});
		$("#iconeDeconnexion").mouseleave(function() {
			$(this).toggleClass("iconeDecnx1");
			$(this).toggleClass("iconeDecnx2");
		});
	});</script>	
	<div id="cadreLogin" style="float:left;top:'.$top.'px;right:'.$right.'px">&nbsp;&nbsp;<i>'.$nomUser.'</i>&nbsp;&nbsp;<span id="sepCadreLogin">|</span> <div id="iconeDeconnexion" class="iconeDecnx1" title="Se déconnecter" onClick="window.location=\'./index.php?logout\'"></div></div>';
} // fin de fonction cadreLoginDeconnexion



//-------------------- partie exécutée ---------------------------------------------------
define('CR',"\n");
define('CRLF',"\r\n");
define('BRCR',"<br/>\n");
define('BR',"<br/>");

if (file_exists('./config/config.inc.php')) include('./config/config.inc.php');

?>