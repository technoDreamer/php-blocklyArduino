<?php
include('./config/config.inc.php');

if ($USE_SCRIBE) { //on utilise l'authentification CAS par le scribe

	include ('./inc/f_session_scribe.inc.php');
	//doit être appelé avant initSession qui utilise le ldap pour trouver les infos sur l'utilisateur courant
	include ('./inc/f_ldap_scribe.inc.php');

} else { //pas scribe

	include ('./inc/f_session_sans_scribe.inc.php');

}

include_once('./inc/c_parametres.inc.php');
include_once('./inc/f_mysql.inc.php');

include_once('./inc/f_blocklyArduino.inc.php');

initSession();

recupUid();

$action='liste';
if (!empty($_GET['action'])) $action=$_GET['action'];

switch ($action) {
	case 'liste': //afichage d'une liste de fichier
		$listeFic=listeFicOpen($uid); //récupère la liste
		echo presenteListeFichiers($listeFic);	//affiche la liste
		break;
	case 'supp': //suppression d'un fichier
		if (empty($_GET['nom']) || empty($_GET['uid'])) break; //infos incomplètes... on sort
		$nom=$_GET['nom'];
		$uid=$_GET['uid'];
		$rqt="SELECT dateHeure FROM fichiers WHERE `nom` LIKE '$nom' AND `user` LIKE '$uid'";
		//echo CR;
		$res=$mysqli->query($rqt);
		if ($res) while(list($_dateH)=$res->fetch_row()) {
			$nbMemeNom++;
			$exFic[]=$uid.'-'.$_dateH.'.xml';
		}
	
		if ($nbMemeNom>0) { //le(s) fichier(s) existait et doit être supprimé
			foreach ($exFic as $_fic) {
				$_nomF='../'.$cheminFichiersXML.'/'.$_fic;
				if (file_exists($_nomF)) unlink($_nomF); //on supprime le (ou les) fichiers existant avec le même nom
			}
			//echo
			$rqt="DELETE FROM `fichiers` WHERE `nom` LIKE '$nom' AND `user` LIKE '$uid';"; //on supprime les fichiers ayant ce nom dans la base
			//echo CR;
			$res=$mysqli->query($rqt);
			echo "Ok";
		}
		break;
}
?>