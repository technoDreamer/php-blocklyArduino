<?php
include_once('./inc/f_mysql.inc.php');
include_once('./inc/f_blocklyArduino.inc.php');

//print_r($_POST);

//echo /*$_POST['inputxml']."/".*/$_POST['user']."/".$_POST['timeS']."/".$_POST['nomP']."/".$_POST['ecrase'].CR;

if (empty($_POST['inputxml']) || empty($_POST['user']) || empty($_POST['timeS']) || empty($_POST['nomP']) || empty($_POST['ecrase'])) { //une des valeurs est vide => on quitte
	echo '1;Données manquantes pour enregistrer le projet';
	exit();
}

function sauveProjet($nom, $nomFic, $uid, $ts, $ecrase) {
	global $cheminFichiersXML;
	global $mysqli;
	
	$erreurBDD = $erreurFichier = true;

	//on cherche si un fichier au même nom pour le même utilisateur existe déjà
	$nbMemeNom=0;
	//$rqt="SELECT COUNT(*) FROM fichiers WHERE `nom` LIKE '$nom' AND `user` LIKE '$uid'";
	//echo 
	$rqt="SELECT dateHeure FROM fichiers WHERE `nom` LIKE '$nom' AND `user` LIKE '$uid'";
	//echo CR;
	$res=$mysqli->query($rqt);
	if ($res) while(list($_dateH)=$res->fetch_row()) {
		$nbMemeNom++;
		$exFic[]=$uid.'-'.$nom.'-'.$_dateH.'.xml';
	}
	
	if ($_POST['ecrase']=='N') if ($nbMemeNom>0) return "existe"; //si on écrase pas et que le fichier existe déjà, on sort l'erreur
	
	//écriture du nouveau fichier xml sur le disque
	$nbOctetsEcrits=file_put_contents('../'.$cheminFichiersXML.'/'.$nomFic, $_POST['inputxml']);
	if ($nbOctetsEcrits !== false) { //fichier écrit sur le disque
		//echo $nomFic;
		$erreurFichier=false;
	} else return "Erreur lors de l'enregistrement du fichier";

	if ($nbMemeNom>0) { //le(s) fichier(s) existait et doit être supprimé
		foreach ($exFic as $_fic) {
			$_nomF='../'.$cheminFichiersXML.'/'.$_fic;
			if (file_exists($_nomF)) unlink($_nomF); //on supprime le (ou les) fichiers existant avec le même nom
		}
		//echo 
		$rqt="DELETE FROM `fichiers` WHERE `nom` LIKE '$nom' AND `user` LIKE '$uid';"; //on supprime les fichiers ayant ce nom dans la base
		//echo CR;
		$res=$mysqli->query($rqt);
	}
	
	//echo 
	$rqt="INSERT INTO `fichiers` (`idFic`, `nom`, `user`, `dateHeure`) VALUES (NULL, '$nom', '$uid', '$ts');"; //on insert le nouveau
	//	echo CR;
	$res=$mysqli->query($rqt);
	
	//echo 
	$rqt="SELECT nom FROM fichiers WHERE nom LIKE '$nom' AND dateHeure='$ts' AND user LIKE '$uid'";
	//	echo CR;
	$nbLig=0;
	$res=$mysqli->query($rqt);
	if ($res) {
		while (list($_nom)=$res->fetch_row()) {
			$nbLig++;
		}
	}
	if ($nbLig>0) $erreurBDD=false; //enregistrement trouvé
	else return "Erreur lors de l'enregistrement en Base de données";

	return '';
}

$nomFic=$_POST['user'].'-'.$_POST['nomP'].'-'.$_POST['timeS'].'.xml';

if (($msg=sauveprojet($_POST['nomP'],$nomFic,$_POST['user'],$_POST['timeS'], $_POST['ecrase']))!='') { //erreur lors de la sauvegarde du projet
	echo '1;'.$msg;
	exit();
}

echo '0';
?>