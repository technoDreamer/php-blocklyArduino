<?php
/*---------------------------------------------------------------
		f_session_sans_scribe.inc.php
-----------------------------------------------------------------
	Librairie de fonctions pour pour gérer l'authentification
	et la session 
-----------------------------------------------------------------
	auteur : Olivier HACQUARD - Académie de Besançon   
---------------------------------------------------------------*/

//défini la version des infos placées en session dans _user_
// à incrémenter en cas d'amélioration du contenu
define ('VERSION_SESSION_USER', 1);

function getCasUid() {
	//authentification et récupération des informations de l'utilisateur

	$login="adminba";
	infosSession_user_($login);
	return ($login);
	
}

/*--- Fonction ----------------------------------------------------------
  infosSession_user_
 ----------------------
  récupère les infos sur l'utilisateur et les place dans $_SESSION['_user_']
 ----------------------
  Entrée :
 		login
  Sortie :
    session ouverte et infos de l'utilisateur mis en session 
-------------------------------*/
function infosSession_user_($login) {
	/*
	            [nom] => ADMIN
            [prenom] => Admin
            [profil] => administrateur
            [div] => 
            [home] => /home/a/admin
            [groupes] => Array
                (
                    [general] => Array
                        (
                            [0] => DomainAdmins
                            [1] => DomainUsers
                            [2] => PrintOperators
                        )

                    [Base] => Array
                        (
                            [0] => professeurs
                        )

                )

            [login] => admin
            [version] => 1

	*/
	$donneesUtilisateur=array('nom'=>'invité', 'prenom'=>'', 'profil'=>'administrateur', 'login'=>'adminba');
	$_SESSION['_user_']=$donneesUtilisateur;//recupInfosUserLDAP($login); //recup des infos de l'utilisateur dans le ldap
	$_SESSION['_user_']['login']=$login;
	//$_SESSION['_user_']['version']=VERSION_SESSION_USER;
}

//*------------------______________________----------------------

/*--- Fonction ----------------------------------------------------------
  initSession
 ----------------------
  initialise la session pour le service et récupére l'utilisateur connecté
 ----------------------
  Entrée :
 	
  Sortie :
    session ouverte et uid de l'utilisateur mis en session pour le service concerné
-------------------------------*/
function initSession() {
	
	global $uid;

	//ouverture de la session
	@session_start();
	$uid = getCasUid();
}


/*--- Fonction ----------------------------------------------------------
  recupUid
 ----------------------
  lance la session pour le service et récupére l'utilisateur connecté
 ----------------------
  Entrée :
 	
  Sortie :
    session ouverte et uid de l'utilisateur retourné dans la variable $uid
-------------------------------*/
function recupUid() {
	global $uid;

	//demande de déconnexion de la session CAS
	if (isset($_GET['logout'])) {

	}
	@session_start();
	
	//on simule la connexion CAS en tant qu'admin
	$_SESSION['phpCAS']['user']="adminba";
	
	//prend en compte le paramètre d'URL ?logas=xxx comme étant l'uid à appliquer pour afficher les pages
	// le paramètre  ?logas=no   permet de supprimer cet uid de substitution
	if (isset($_GET['logas'])) {
		if (strtolower($_GET['logas'])=='no') unset($_SESSION['logas']);
		else {
			//seul l'admin peut substituer l'identité
			// ne fonctionne que sur un scribe !!!
			if(!empty($_SESSION['phpCAS']['user'])) if ($_SESSION['phpCAS']['user']=='adminba') $_SESSION['logas']=$_GET['logas'];
		}
	}
	if(!empty($_SESSION['phpCAS']['user'])) 
		if (isset($_SESSION['logas'])) {
			$uid=$_SESSION['logas']; 
			$_SESSION['vraiUid']=$_SESSION['phpCAS']['user'];
		} else $uid = $_SESSION['phpCAS']['user']; 
	else { //si on ne récupère pas l'uid
		//on retourne à la page index...
		header("Location: ./index.php"); 
		//arrêt du script... normalement, on ne passe pas par là...	
		exit(); 
	}
	
	//on vérifie que le contenu de $_SESSION['_user_'] est correct
	$sessionPasOk=true;
	if (!isset($_SESSION['_user_'])) $sessionPasOk=true;
	else { //$_SESSION['_user_'] est défini
		if(!isset($_SESSION['_user_']['version'])) $sessionPasOk=true;
		//else if ($_SESSION['_user_']['version']<VERSION_SESSION_USER) $sessionPasOk=true;
	}
	
	if ($sessionPasOk) infosSession_user_($uid);
}

/*------------------------------------------
 Fonction : verifAccesPage
-------------------------------------
 - Vérifie si l'utilisateur a le login ou le profil nécessaire pour accéder. Message d'erreur, sinon
-------------------------------------
 - Entrée :
 - Sortie : 
---------------------------------------------*/

function verifAccesPage() {
	$tabAutorises=array(); //func_get_args();
	
	//on fait un tableau des gens autorisés en fusionnant des paramètres qui peuvent être des tableaux ou des valeurs unitaires
	// par exemple verifAccesPage($compteAutorises,"ohacquard");
	if (func_num_args()>0) foreach (func_get_args() as $elemAutorise) {
		if (is_array($elemAutorise)) $tabAutorises=array_merge($tabAutorises,$elemAutorise);
		else $tabAutorises[]=$elemAutorise;
	}
	//print_r($tabAutorises);exit();
	
	//global $uid;
	$pasAutorise=true;
	
	$tCompare=array(strtolower($_SESSION['_user_']['profil']),strtolower($_SESSION['_user_']['login']));
	if (isset($_SESSION['_user_']['groupes']))
		if (is_array($_SESSION['_user_']['groupes']))
			foreach($_SESSION['_user_']['groupes'] as $_type=>$_tGrp) {
				$tCompare=array_merge($tCompare, $_tGrp);
			}
	//print_r($_SESSION);
	if (is_array($tCompare)) foreach ($tCompare as $_idx=>$_grp) $tCompare[$_idx]=strtolower($_grp);
	//print_r($tCompare);
	//print_r($tabAutorises);//exit();
	
	if (is_array($tabAutorises)) foreach($tabAutorises as $autorise) {
		//si on trouve un des noms autorisés dans le groupe ou le login de l'utilisateur en cours, on l'autorise
		if (is_array($autorise)) {
			foreach ($autorise as $_autorise) if (in_array(strtolower($_autorise),$tCompare)) $pasAutorise=false;
		} else {
			if (in_array(strtolower($autorise),$tCompare)) $pasAutorise=false;
		}
		
	}
	//si pas autorisé... on sort
	if ($pasAutorise) header("Location: ./inc/pasAutorise.html"); 
}

?>