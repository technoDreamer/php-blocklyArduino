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

include_once('./inc/f_mysql.inc.php');

function getCasUid() {
	//authentification et récupération des informations de l'utilisateur

	//à changer par la fenêtre d'authentification !!!
	if (!empty($_GET['action'])) { 
	//if (!empty($_GET['connecte'])) {
	//	if ($_GET['connecte']=="no") return false;
		if ($_GET['action']!='login') return false;
		//print_r($_POST);exit();
		$login=$_GET['login']; //"adminba";
		if (verifPwd($login, $_GET['pwd'])) {
			infosSession_user_($login);
			//print_r($_SESSION);exit();
			return ($login);
		} else {
			$_SESSION['error']='login';
		}
	}
	return false;
}

/*--- Fonction ----------------------------------------------------------
  verifPwd
 ----------------------
  vérifie que le mot de passe saisi est correcte
 ----------------------
  Entrée :
 		login, pwd
  Sortie :
    booléen à vrai ou faux selon le résultat 
-------------------------------*/
function verifPwd($login, $pwd) {
	global $mysqli;
	echo $query="SELECT login FROM utilisateurs WHERE login='".secMy($login)."' AND (pwd='".md5($pwd)."' OR pwd='$pwd')";
	$res=$mysqli->query($query);
	if ($res) {
		if (list($_log)=$res->fetch_row()) return true;
	}
	return false;
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
	if (empty($login)) return 0;
	global $mysqli;
	$query="SELECT nom,prenom,intitule_profil,mail FROM utilisateurs,profils_utilisateurs WHERE login='$login' AND profil=id_profil LIMIT 1";
	$res=$mysqli->query($query);
	if ($res) 
		while (list($_nom,$_prenom,$_profil,$_mail)=$res->fetch_row()) {
			$donneesUtilisateur=array('nom'=>$_nom, 'prenom'=>$_prenom, 'mail'=>$_mail, 'profil'=>$_profil, 'login'=>$login);
		}	
	//$donneesUtilisateur=array('nom'=>'invité', 'prenom'=>'', 'profil'=>'administrateur', 'login'=>'adminba');
	$_SESSION['_user_']=$donneesUtilisateur;//recupInfosUserLDAP($login); //recup des infos de l'utilisateur dans le ldap
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
	if (isset($_GET['logout'])) {
		unset($_SESSION['_user_']); //on vide la session
		unset($_SESSION['premLancement']);
	}
	$uid = getCasUid();
	if (empty($_SESSION['premLancement'])) $_SESSION['premLancement']=1;
	
//	print_r($_SESSION);
//	exit();
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

	@session_start();
	//demande de déconnexion de la session CAS
	if (isset($_GET['logout'])) {
		unset($_SESSION['_user_']); //on vide la session
		unset($_SESSION['premLancement']);
	}
	
	//on simule la connexion CAS en tant qu'admin
	//$_SESSION['phpCAS']['_user_']="adminba";
	
	//prend en compte le paramètre d'URL ?logas=xxx comme étant l'uid à appliquer pour afficher les pages
	// le paramètre  ?logas=no   permet de supprimer cet uid de substitution
	if (isset($_GET['logas'])) {
		if (strtolower($_GET['logas'])=='no') unset($_SESSION['logas']);
		else {
			//seul l'admin peut substituer l'identité
			// ne fonctionne que sur un scribe !!!
			if(!empty($_SESSION['_user_']['login'])) if ($_SESSION['_user_']['login']=='admin') $_SESSION['logas']=$_GET['logas'];
		}
	}
	if(!empty($_SESSION['_user_']['login']))  //la session est déjà ouverte
		if (isset($_SESSION['logas'])) {
			$uid=$_SESSION['logas']; 
			$_SESSION['vraiUid']=$_SESSION['_user_']['login'];
		} else $uid = $_SESSION['_user_']['login']; 
	else { //si on ne récupère pas l'uid
		//on retourne à la page index...
		//header("Location: ./index.php"); 
		//arrêt du script... normalement, on ne passe pas par là...	
		//exit();
		 
	}
	
	if (empty($_SESSION['premLancement'])) $_SESSION['premLancement']=1;
	
	//on vérifie que le contenu de $_SESSION['_user_'] est correct
	$sessionPasOk=true;
	if (!isset($_SESSION['_user_']['login'])) $sessionPasOk=true;
	else { //$_SESSION['_user_']['login'] est défini
		//if(!isset($_SESSION['_user_']['version'])) $sessionPasOk=true;
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