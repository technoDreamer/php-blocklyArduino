<?php
/*---------------------------------------------------------------
		index.php
-----------------------------------------------------------------
	page de base du service blockly@rduino
-----------------------------------------------------------------
	auteur : Olivier HACQUARD - Académie de Besançon   
---------------------------------------------------------------*/
chdir('./php');

include('./config/config.inc.php');

if ($USE_SCRIBE) { //on utilise l'authentification CAS par le scribe

	include ('./inc/f_session_scribe.inc.php');
	//doit être appelé avant initSession qui utilise le ldap pour trouver les infos sur l'utilisateur courant
	include ('./inc/f_ldap_scribe.inc.php');

} else { //pas scribe

	include ('./inc/f_session_sans_scribe.inc.php');

}
	//initialisation de la Session et recup de l'uid, sinon, on retourne à l'authentification du CAS
	initSession();

//------------------- Controler ----------------------

//------------------- Module -------------------------

//on va à la page du service
header("Location: ./blocklyArduino.php"); 

//------------------- View -------------------------

?>