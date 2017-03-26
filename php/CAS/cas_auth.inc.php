<?php
/*
	cas_auth.php
	
 Bibliotèque de fonctions pour utiliser l'authentification par le CAS dans les modules Envole
*/

//inclue le bon dossier système pour le cas
$path = explode(":", ini_get('include_path')); //get all the possible paths to the file 
foreach($path as $chemin) 
  { 
  	if (file_exists("$chemin/CAS-1.3.1/eoleCAS.php")) include_once("$chemin/CAS-1.3.1/eoleCAS.php"); //scribe 2.3
  	else if (file_exists("$chemin/CAS/eoleCAS.php")) include_once("$chemin/CAS/eoleCAS.php"); //scribe 2.2
  }
include_once('configCAS/cas.inc.php'); //('cas.inc.php');

if (!defined("__CAS_URL")) define("__CAS_URL", __CAS_FOLDER); 

function initCasProxy(){
    /*
     * Initialise le client cas en mode proxy
     * pour permettre la récupération de ticket proxy
     */
    EolephpCAS::proxy(__CAS_VERSION,__CAS_SERVER,__CAS_PORT,__CAS_FOLDER,false);
    //setLang();
    if (!defined(__CAS_VALIDER_CA)) define("__CAS_VALIDER_CA", false);
    if (!defined(__CAS_LOGOUT)) define("__CAS_LOGOUT", false);
    
    if (__CAS_VALIDER_CA) {
        EolephpCAS::setCasServerCACert(__CAS_CA_LOCATION);
    } else {
        if (method_exists(EolephpCAS, 'setNoCasServerValidation')){
            EolephpCAS::setNoCasServerValidation();
        }
    }
    if (__CAS_LOGOUT){
        if (method_exists(EolephpCAS, 'eolelogoutRequests')){
            EolephpCAS::EoleLogoutRequests(false);
        }
    }
}


function cas_instance(){
	  EolephpCAS::client(__CAS_VERSION, __CAS_SERVER, __CAS_PORT, __CAS_URL, false);
	  
    if (!defined(__CAS_VALIDER_CA)) define("__CAS_VALIDER_CA", false);
    if (!defined(__CAS_LOGOUT)) define("__CAS_LOGOUT", false);
   
   if (__CAS_VALIDER_CA) {
        EolephpCAS::setCasServerCACert(__CAS_CA_LOCATION);
    } else {
        if (method_exists(EolephpCAS, 'setNoCasServerValidation')){
            EolephpCAS::setNoCasServerValidation();
        }
    }
    if (__CAS_LOGOUT){
        if (method_exists(EolephpCAS, 'eolelogoutRequests')){
            EolephpCAS::EoleLogoutRequests(false);
        }
    }
}


function cas_verif(){
	return EolephpCAS::checkAuthentication();
    //return EolephpCAS::isAuthenticated();
}


function cas_auth(){
    EolephpCAS::forceAuthentication();
}


function cas_logout(){
    EolephpCAS::logout(array("url"=>$_SERVER["SCRIPT_URI"]));
}

function cas_details() {
	  return EolephpCAS::getDetails();
}
?>
